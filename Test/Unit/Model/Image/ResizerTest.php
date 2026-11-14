<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Test\Unit\Model\Image;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\Image\Resizer;

/**
 * An image replaced by a file with the same name must show up: the resized copy is made
 * again and its URL changes, so browsers do not keep the old picture.
 *
 * @requires extension gd
 */
class ResizerTest extends TestCase
{
    private const MEDIA_URL = 'https://shop.test/media/';

    /** @var string */
    private string $media;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP support');
        }
        $this->media = sys_get_temp_dir() . '/slova-blog-resizer-' . bin2hex(random_bytes(4));
        mkdir($this->media . '/slova/blog', 0777, true);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->media, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->media);
    }

    /**
     * @return void
     */
    public function testReplacedImageIsResizedAgainUnderANewUrl(): void
    {
        $resizer = $this->resizer();
        $this->writeJpeg('slova/blog/photo.jpg', 1000, 500, [255, 0, 0], time() - 120);

        $first = $resizer->process('slova/blog/photo.jpg', 400);
        $this->assertStringStartsWith(self::MEDIA_URL . 'slova_blog_resized/webp/400/slova/blog/photo.webp?v=', $first);
        $this->assertSame([400, 200, [255, 0, 0]], $this->describe($first));
        // The copy was made a minute ago…
        touch($this->media . '/slova_blog_resized/webp/400/slova/blog/photo.webp', time() - 60);

        // …and now the original is replaced: same name, new picture.
        $this->writeJpeg('slova/blog/photo.jpg', 800, 800, [0, 0, 255], time());

        $second = $resizer->process('slova/blog/photo.jpg', 400);
        $this->assertNotSame($first, $second, 'the URL must change with the picture');
        $this->assertSame([400, 400, [0, 0, 255]], $this->describe($second));
        $this->assertSame([], glob($this->media . '/slova_blog_resized/webp/400/slova/blog/*.tmp'));
    }

    /**
     * @return void
     */
    public function testUpToDateCopyIsReused(): void
    {
        $resizer = $this->resizer();
        $this->writeJpeg('slova/blog/photo.jpg', 1000, 500, [0, 128, 0], time() - 120);

        $url = $resizer->process('slova/blog/photo.jpg', 400);
        $copy = $this->media . '/slova_blog_resized/webp/400/slova/blog/photo.webp';
        touch($copy, time() - 60);
        clearstatcache();

        $this->assertSame($url, $resizer->process('slova/blog/photo.jpg', 400));
        clearstatcache();
        $this->assertSame(time() - 60, filemtime($copy), 'an up-to-date copy must not be written again');
    }

    /**
     * @return void
     */
    public function testMissingSourceFallsBackToTheOriginalUrl(): void
    {
        $this->assertSame(self::MEDIA_URL . 'slova/blog/none.jpg', $this->resizer()->process('slova/blog/none.jpg', 400));
    }

    /**
     * Resizer on the temporary media directory.
     *
     * @return Resizer
     */
    private function resizer(): Resizer
    {
        $directory = $this->createMock(WriteInterface::class);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($path) => $this->media . '/' . ltrim((string)$path, '/'));
        $directory->method('isDirectory')->willReturnCallback(fn ($path) => is_dir($this->media . '/' . $path));
        $directory->method('create')->willReturnCallback(function ($path) {
            $dir = $this->media . '/' . $path;
            return is_dir($dir) || mkdir($dir, 0777, true);
        });

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($directory);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn(self::MEDIA_URL);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $config = $this->createMock(Config::class);
        $config->method('isResponsiveImagesEnabled')->willReturn(true);

        return new Resizer($filesystem, $storeManager, $config, $this->createMock(LoggerInterface::class));
    }

    /**
     * Write a one-colour JPEG with the given modification time.
     *
     * @param string $path
     * @param int $width
     * @param int $height
     * @param int[] $rgb
     * @param int $mtime
     * @return void
     */
    private function writeJpeg(string $path, int $width, int $height, array $rgb, int $mtime): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        imagejpeg($image, $this->media . '/' . $path, 95);
        imagedestroy($image);
        touch($this->media . '/' . $path, $mtime);
        clearstatcache();
    }

    /**
     * Size and (rounded) colour of the copy behind a URL.
     *
     * @param string $url
     * @return array{0:int,1:int,2:int[]}
     */
    private function describe(string $url): array
    {
        $file = $this->media . '/' . preg_replace('#^' . preg_quote(self::MEDIA_URL, '#') . '|\?.*$#', '', $url);
        $image = imagecreatefromwebp($file);
        $color = imagecolorsforindex($image, imagecolorat($image, 10, 10));
        $round = fn (int $c): int => $c < 64 ? 0 : ($c > 191 ? 255 : 128);
        $result = [imagesx($image), imagesy($image), [$round($color['red']), $round($color['green']), $round($color['blue'])]];
        imagedestroy($image);
        return $result;
    }
}
