<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Slova\Blog\Model\Config;

/**
 * Resizes a media image to a width and format (WebP, JPEG or PNG) and returns its URL.
 *
 * Copies are made on first use with GD and kept in
 * pub/media/slova_blog_resized/{format}/{width}/{original path}. Images are never
 * enlarged. When resizing is turned off, unsupported or fails, the original URL is
 * returned, so a page never shows a broken image.
 *
 * A copy older than its original is made again, so an image replaced by a file with
 * the same name shows up without clearing anything. The URL of a copy carries the
 * original's modification time (?v=…), so browsers and CDNs fetch the new picture
 * instead of the one they cached under the same address.
 */
class Resizer
{
    public const CACHE_DIR = 'slova_blog_resized';

    private const QUALITY_WEBP = 75;
    private const QUALITY_JPEG = 78;

    /** @var array<string,string|null> content hashes by media path, for this request */
    private array $fingerprints = [];

    /**
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * URL of the image at the given width and format; '' for an empty path.
     *
     * @param string $sourcePath path relative to pub/media
     * @param int $width
     * @param string $format webp|jpg|png
     * @return string
     */
    public function process(string $sourcePath, int $width, string $format = 'webp'): string
    {
        $sourcePath = ltrim($sourcePath, '/');
        if ($sourcePath === '' || $width <= 0) {
            return '';
        }
        if (!$this->canResize($sourcePath, $format)) {
            return $this->mediaUrl($sourcePath);
        }

        $cachedPath = $this->cachedPath($sourcePath, $width, $format);
        $sourceTime = $this->modifiedTime($sourcePath);

        if ($this->isOutdated($cachedPath, $sourceTime)) {
            try {
                $this->generate($sourcePath, $width, $format, $cachedPath);
            } catch (\Throwable $e) {
                $this->logger->warning('Slova_Blog: could not resize ' . $sourcePath, [
                    'width' => $width,
                    'format' => $format,
                    'error' => $e->getMessage(),
                ]);
                return $this->mediaUrl($sourcePath);
            }
        }

        return $this->versioned($this->mediaUrl($cachedPath), $sourceTime);
    }

    /**
     * Whether the copy is missing or older than its original (the original was replaced).
     *
     * @param string $cachedPath
     * @param int $sourceTime
     * @return bool
     */
    private function isOutdated(string $cachedPath, int $sourceTime): bool
    {
        $cachedAbs = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($cachedPath);
        if (!is_file($cachedAbs)) {
            return true;
        }
        return $sourceTime > 0 && (int)filemtime($cachedAbs) < $sourceTime;
    }

    /**
     * Modification time of a media file, 0 when it is missing.
     *
     * @param string $path
     * @return int
     */
    private function modifiedTime(string $path): int
    {
        $absolute = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($path);
        return is_file($absolute) ? (int)filemtime($absolute) : 0;
    }

    /**
     * Add the original's modification time to the URL, so a replaced image gets a new address.
     *
     * @param string $url
     * @param int $sourceTime
     * @return string
     */
    private function versioned(string $url, int $sourceTime): string
    {
        return $sourceTime > 0 ? $url . '?v=' . base_convert((string)$sourceTime, 10, 36) : $url;
    }

    /**
     * Whether resized copies are made at all (setting on, GD present).
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return extension_loaded('gd') && $this->config->isResponsiveImagesEnabled();
    }

    /**
     * Size the image is shown at when drawn at most $width pixels wide (never enlarged).
     *
     * @param string $sourcePath
     * @param int $width
     * @return array{0:int,1:int} [width, height]; [0, 0] when unreadable
     */
    public function dimensionsAt(string $sourcePath, int $width): array
    {
        $sourcePath = ltrim($sourcePath, '/');
        if ($sourcePath === '' || $this->isSvg($sourcePath)) {
            return [0, 0];
        }
        $absolute = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($sourcePath);
        if (!is_file($absolute)) {
            return [0, 0];
        }
        $info = @getimagesize($absolute); // phpcs:ignore Generic.PHP.NoSilencedErrors
        if (!$info) {
            return [0, 0];
        }
        [$srcW, $srcH] = [(int)$info[0], (int)$info[1]];
        if ($srcW <= 0 || $srcW <= $width) {
            return [$srcW, $srcH];
        }
        return [$width, (int)round($srcH * $width / $srcW)];
    }

    /**
     * Whether two media paths are the same picture: the same file, or two files with
     * identical content (the same photo uploaded twice under different names).
     *
     * @param string $pathA
     * @param string $pathB
     * @return bool
     */
    public function isSameImage(string $pathA, string $pathB): bool
    {
        $pathA = ltrim($pathA, '/');
        $pathB = ltrim($pathB, '/');
        if ($pathA === '' || $pathB === '') {
            return false;
        }
        if ($pathA === $pathB) {
            return true;
        }
        $hashA = $this->fingerprint($pathA);
        return $hashA !== null && $hashA === $this->fingerprint($pathB);
    }

    /**
     * Content hash of a media file, or null when it cannot be read.
     *
     * @param string $path
     * @return string|null
     */
    private function fingerprint(string $path): ?string
    {
        if (!array_key_exists($path, $this->fingerprints)) {
            $absolute = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($path);
            $hash = is_file($absolute) ? md5_file($absolute) : false;
            $this->fingerprints[$path] = $hash !== false ? $hash : null;
        }
        return $this->fingerprints[$path];
    }

    /**
     * Absolute URL of a path under pub/media.
     *
     * @param string $path
     * @return string
     */
    public function mediaUrl(string $path): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($path, '/');
    }

    /**
     * Whether this source can be turned into this format here.
     *
     * @param string $sourcePath
     * @param string $format
     * @return bool
     */
    private function canResize(string $sourcePath, string $format): bool
    {
        if (!$this->isEnabled() || $this->isSvg($sourcePath)) {
            return false;
        }
        return match ($format) {
            'webp' => function_exists('imagewebp'),
            'jpg', 'jpeg' => function_exists('imagejpeg'),
            'png' => function_exists('imagepng'),
            default => false,
        };
    }

    /**
     * Write the resized copy.
     *
     * @param string $sourcePath
     * @param int $width
     * @param string $format
     * @param string $cachedPath
     * @return void
     * @throws \RuntimeException
     */
    private function generate(string $sourcePath, int $width, string $format, string $cachedPath): void
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $srcAbs = $mediaDir->getAbsolutePath($sourcePath);
        if (!is_file($srcAbs)) {
            throw new \RuntimeException('Source not found: ' . $sourcePath);
        }

        $info = @getimagesize($srcAbs); // phpcs:ignore Generic.PHP.NoSilencedErrors
        if (!$info) {
            throw new \RuntimeException('Cannot read image: ' . $sourcePath);
        }
        [$srcW, $srcH, $type] = [(int)$info[0], (int)$info[1], (int)$info[2]];
        if ($srcW <= 0 || $srcH <= 0) {
            throw new \RuntimeException('Invalid dimensions: ' . $sourcePath);
        }

        if ($srcW <= $width) {
            [$dstW, $dstH] = [$srcW, $srcH];
        } else {
            $dstW = $width;
            $dstH = max(1, (int)round($srcH * $width / $srcW));
        }

        $src = $this->openImage($srcAbs, $type);
        if (!$src) {
            throw new \RuntimeException('Unsupported source format: ' . $sourcePath);
        }

        $dst = imagecreatetruecolor($dstW, $dstH);
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $cacheDir = dirname($cachedPath);
        if (!$mediaDir->isDirectory($cacheDir)) {
            $mediaDir->create($cacheDir);
        }
        $dstAbs = $mediaDir->getAbsolutePath($cachedPath);
        // Written beside the copy and renamed over it: a copy made again (original replaced)
        // may be served to other visitors at that moment, and must never be half-written.
        $tmpAbs = $dstAbs . '.' . bin2hex(random_bytes(4)) . '.tmp';

        $ok = match ($format) {
            'webp' => imagewebp($dst, $tmpAbs, self::QUALITY_WEBP),
            'jpg', 'jpeg' => $this->saveJpeg($dst, $tmpAbs),
            'png' => imagepng($dst, $tmpAbs),
            default => false,
        };

        imagedestroy($src);
        imagedestroy($dst);

        // phpcs:ignore Generic.PHP.NoSilencedErrors
        if (!$ok || !@rename($tmpAbs, $dstAbs)) {
            @unlink($tmpAbs); // phpcs:ignore Generic.PHP.NoSilencedErrors
            throw new \RuntimeException('Failed to write ' . $cachedPath);
        }
    }

    /**
     * JPEG has no alpha channel: flatten onto white first.
     *
     * @param \GdImage $img
     * @param string $path
     * @return bool
     */
    private function saveJpeg(\GdImage $img, string $path): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $bg = imagecreatetruecolor($w, $h);
        imagefilledrectangle($bg, 0, 0, $w, $h, imagecolorallocate($bg, 255, 255, 255));
        imagecopy($bg, $img, 0, 0, 0, 0, $w, $h);
        $ok = imagejpeg($bg, $path, self::QUALITY_JPEG);
        imagedestroy($bg);
        return $ok;
    }

    /**
     * Open a source image with GD.
     *
     * @param string $absPath
     * @param int $type
     * @return \GdImage|null
     */
    private function openImage(string $absPath, int $type): ?\GdImage
    {
        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absPath), // phpcs:ignore Generic.PHP.NoSilencedErrors
            IMAGETYPE_PNG => @imagecreatefrompng($absPath), // phpcs:ignore Generic.PHP.NoSilencedErrors
            IMAGETYPE_GIF => @imagecreatefromgif($absPath), // phpcs:ignore Generic.PHP.NoSilencedErrors
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($absPath) // phpcs:ignore Generic.PHP.NoSilencedErrors
                : false,
            default => false,
        };
        return $img ?: null;
    }

    /**
     * Path of the resized copy under pub/media.
     *
     * @param string $sourcePath
     * @param int $width
     * @param string $format
     * @return string
     */
    private function cachedPath(string $sourcePath, int $width, string $format): string
    {
        $info = pathinfo($sourcePath);
        $name = ($info['filename'] ?? 'image') . '.' . ($format === 'jpeg' ? 'jpg' : $format);
        $dir = trim(($info['dirname'] ?? '') === '.' ? '' : (string)($info['dirname'] ?? ''), '/');
        return self::CACHE_DIR . '/' . $format . '/' . $width . '/' . ($dir === '' ? $name : $dir . '/' . $name);
    }

    /**
     * Whether the path is an SVG (vector: served as is).
     *
     * @param string $path
     * @return bool
     */
    private function isSvg(string $path): bool
    {
        return strtolower((string)pathinfo($path, PATHINFO_EXTENSION)) === 'svg';
    }
}
