<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Service;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Slova\Blog\Model\Image\Resizer;

/**
 * Prepares the WYSIWYG HTML of a post for display:
 * - images from pub/media become <picture> with WebP and JPEG copies (1x/2x),
 *   with width and height so the page does not shift while they load;
 * - every <img> gets loading="lazy" and decoding="async" unless set;
 * - iframes are lazy-loaded; YouTube/Vimeo embeds get the usual permissions;
 * - [youtube:VIDEO_ID] becomes a light thumbnail that links to the video.
 */
class ContentProcessor
{
    private const YOUTUBE_SHORTCODE = '/\[youtube:([a-zA-Z0-9_-]{6,20})\]/';
    private const DEFAULT_IMAGE_WIDTH = 900;
    private const BOOLEAN_ATTRIBUTES = ['allowfullscreen', 'async', 'defer', 'autoplay', 'controls', 'loop', 'muted'];

    /**
     * @param Resizer $resizer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Resizer $resizer,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Process post HTML.
     *
     * @param string $html
     * @return string
     */
    public function process(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $html = $this->processYoutubeShortcodes($html);
        $html = $this->processImages($html);
        return $this->processIframes($html);
    }

    /**
     * Media paths (relative to pub/media) of the images in the HTML; external images are left out.
     *
     * @param string $html
     * @return string[]
     */
    public function getMediaImagePaths(string $html): array
    {
        preg_match_all('/<img\b([^>]*)>/i', $html, $matches);
        $paths = [];
        foreach ($matches[1] as $rawAttributes) {
            $path = $this->urlToMediaPath((string)($this->parseAttributes($rawAttributes)['src'] ?? ''));
            if ($path !== null && $path !== '') {
                $paths[] = $path;
            }
        }
        return array_values(array_unique($paths));
    }

    /**
     * Rewrite every <img>.
     *
     * @param string $html
     * @return string
     */
    private function processImages(string $html): string
    {
        return (string)preg_replace_callback(
            '/<img\b([^>]*)>/i',
            function (array $match): string {
                $attrs = $this->parseAttributes($match[1]);
                $attrs['loading'] ??= 'lazy';
                $attrs['decoding'] ??= 'async';

                $mediaPath = $this->urlToMediaPath((string)($attrs['src'] ?? ''));
                if ($mediaPath === null) {
                    return '<img' . $this->renderAttributes($attrs) . '>';
                }

                $width = isset($attrs['width']) ? max(1, (int)$attrs['width']) : self::DEFAULT_IMAGE_WIDTH;
                [$naturalW, $naturalH] = $this->resizer->dimensionsAt($mediaPath, $width);
                if ($naturalW > 0 && $naturalH > 0 && !isset($attrs['width']) && !isset($attrs['height'])) {
                    $attrs['width'] = (string)$naturalW;
                    $attrs['height'] = (string)$naturalH;
                }

                if (!$this->resizer->isEnabled()) {
                    return '<img' . $this->renderAttributes($attrs) . '>';
                }

                $original = $this->resizer->mediaUrl($mediaPath);
                $webp1x = $this->resizer->process($mediaPath, $width, 'webp');
                if ($webp1x === '' || $webp1x === $original) {
                    return '<img' . $this->renderAttributes($attrs) . '>';
                }
                $webp2x = $this->resizer->process($mediaPath, $width * 2, 'webp');
                $jpg1x = $this->resizer->process($mediaPath, $width, 'jpg');
                $jpg2x = $this->resizer->process($mediaPath, $width * 2, 'jpg');

                $attrs['src'] = $jpg1x;
                $attrs['srcset'] = $this->srcset($jpg1x, $jpg2x);

                return sprintf(
                    '<picture><source type="image/webp" srcset="%s"><img%s></picture>',
                    htmlspecialchars($this->srcset($webp1x, $webp2x), ENT_QUOTES, 'UTF-8'),
                    $this->renderAttributes($attrs)
                );
            },
            $html
        );
    }

    /**
     * Lazy-load iframes; add the usual permissions to video embeds.
     *
     * @param string $html
     * @return string
     */
    private function processIframes(string $html): string
    {
        return (string)preg_replace_callback(
            '/<iframe\b([^>]*)>/i',
            function (array $match): string {
                $attrs = $this->parseAttributes($match[1]);
                $attrs['loading'] ??= 'lazy';
                if ($this->isVideoEmbed((string)($attrs['src'] ?? ''))) {
                    $attrs['allow'] ??= 'accelerometer; encrypted-media; gyroscope; picture-in-picture';
                    $attrs['allowfullscreen'] ??= '';
                    $attrs['referrerpolicy'] ??= 'strict-origin-when-cross-origin';
                }
                return '<iframe' . $this->renderAttributes($attrs) . '>';
            },
            $html
        );
    }

    /**
     * Replace [youtube:ID] with a thumbnail link: no YouTube script or cookie until clicked.
     *
     * @param string $html
     * @return string
     */
    private function processYoutubeShortcodes(string $html): string
    {
        return (string)preg_replace_callback(
            self::YOUTUBE_SHORTCODE,
            static function (array $match): string {
                $videoId = $match[1];
                return sprintf(
                    '<a href="%s" class="slova-blog-yt" target="_blank" rel="noopener noreferrer" aria-label="%s">'
                    . '<img src="%s" alt="" loading="lazy" decoding="async" width="1280" height="720">'
                    . '<span class="slova-blog-yt__btn" aria-hidden="true">▶</span></a>',
                    htmlspecialchars('https://www.youtube.com/watch?v=' . $videoId, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)__('Watch on YouTube'), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars('https://i.ytimg.com/vi/' . $videoId . '/maxresdefault.jpg', ENT_QUOTES, 'UTF-8')
                );
            },
            $html
        );
    }

    /**
     * "a 1x, b 2x", or just "a" when the 2x copy is the same file (small originals).
     *
     * @param string $x1
     * @param string $x2
     * @return string
     */
    private function srcset(string $x1, string $x2): string
    {
        return $x2 !== '' && $x2 !== $x1 ? $x1 . ' 1x, ' . $x2 . ' 2x' : $x1;
    }

    /**
     * Whether the URL is a YouTube or Vimeo embed.
     *
     * @param string $url
     * @return bool
     */
    private function isVideoEmbed(string $url): bool
    {
        $host = $url !== '' ? (string)parse_url($url, PHP_URL_HOST) : '';
        return str_contains($host, 'youtube.com')
            || str_contains($host, 'youtube-nocookie.com')
            || str_contains($host, 'vimeo.com');
    }

    /**
     * Path under pub/media for a media URL of this store, or null for anything else.
     *
     * @param string $url
     * @return string|null
     */
    private function urlToMediaPath(string $url): ?string
    {
        if ($url === '') {
            return null;
        }
        try {
            $baseMedia = (string)$this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (\Throwable) {
            return null;
        }
        if ($baseMedia !== '' && str_starts_with($url, $baseMedia)) {
            return substr($url, strlen($baseMedia));
        }

        // Relative URLs ("/media/…") and the same store on another domain alias.
        $path = (string)parse_url($url, PHP_URL_PATH);
        $host = (string)parse_url($url, PHP_URL_HOST);
        $mediaHost = (string)parse_url($baseMedia, PHP_URL_HOST);
        if ($host !== '' && $host !== $mediaHost) {
            return null;
        }
        $pos = strpos($path, '/media/');
        return $pos !== false ? ltrim(substr($path, $pos + 7), '/') : null;
    }

    /**
     * Attributes of a tag as name => value.
     *
     * @param string $raw
     * @return array<string,string>
     */
    private function parseAttributes(string $raw): array
    {
        $attrs = [];
        preg_match_all(
            '/([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/',
            $raw,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $m) {
            $value = ($m[2] ?? '') !== '' ? $m[2] : (($m[3] ?? '') !== '' ? $m[3] : ($m[4] ?? ''));
            $attrs[strtolower($m[1])] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $attrs;
    }

    /**
     * Render attributes back to HTML.
     *
     * @param array<string,string> $attrs
     * @return string
     */
    private function renderAttributes(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $name => $value) {
            if ($name === '/') {
                continue;
            }
            if ($value === '' && in_array($name, self::BOOLEAN_ATTRIBUTES, true)) {
                $out .= ' ' . $name;
            } else {
                $out .= ' ' . $name . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return $out;
    }
}
