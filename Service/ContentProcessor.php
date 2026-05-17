<?php
declare(strict_types=1);

namespace Slova\Blog\Service;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Slova\Imaging\Service\ImageProcessor;

/**
 * Post-processes WYSIWYG HTML for performance & accessibility:
 * - adds loading="lazy" + decoding="async" to inline <img>
 * - injects width/height when the source can be measured (CLS=0)
 * - lazy-loads iframes (YouTube, Vimeo, etc.)
 * - expands [youtube:VIDEO_ID] shortcode to a click-to-load thumbnail
 */
class ContentProcessor
{
    private const YOUTUBE_SHORTCODE = '/\[youtube:([a-zA-Z0-9_-]{6,20})\]/';

    public function __construct(
        private readonly ImageProcessor $imageProcessor,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function process(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $html = $this->processYoutubeShortcodes($html);
        $html = $this->processImages($html);
        $html = $this->processIframes($html);

        return $html;
    }

    private function processImages(string $html): string
    {
        return (string)preg_replace_callback(
            '/<img\b([^>]*)>/i',
            function (array $m): string {
                $attrs = $this->parseAttributes($m[1]);

                if (!isset($attrs['loading'])) {
                    $attrs['loading'] = 'lazy';
                }
                if (!isset($attrs['decoding'])) {
                    $attrs['decoding'] = 'async';
                }

                $src = (string)($attrs['src'] ?? '');
                $mediaPath = $this->urlToMediaPath($src);

                if (!$mediaPath) {
                    // External/unknown URL — just enhance attributes and return.
                    return '<img' . $this->renderAttributes($attrs) . '>';
                }

                $baseWidth = isset($attrs['width']) ? max(1, (int)$attrs['width']) : 900;

                [$natW, $natH] = $this->imageProcessor->dimensionsAt($mediaPath, $baseWidth);
                if ($natW > 0 && $natH > 0) {
                    $attrs['width']  = $attrs['width']  ?? (string)$natW;
                    $attrs['height'] = $attrs['height'] ?? (string)$natH;
                }

                $webp1x = $this->imageProcessor->process($mediaPath, $baseWidth, 'webp');
                $webp2x = $this->imageProcessor->process($mediaPath, $baseWidth * 2, 'webp');
                $jpg1x  = $this->imageProcessor->process($mediaPath, $baseWidth, 'jpg');
                $jpg2x  = $this->imageProcessor->process($mediaPath, $baseWidth * 2, 'jpg');

                $rawMediaUrl = $this->mediaUrl($mediaPath);
                if ($webp1x === '' || $webp1x === $rawMediaUrl) {
                    // Processing failed entirely — leave the original <img>.
                    return '<img' . $this->renderAttributes($attrs) . '>';
                }

                $webpSrcset = $webp1x . ' 1x' . ($webp2x !== $webp1x && $webp2x !== '' ? ', ' . $webp2x . ' 2x' : '');
                $jpgSrcset  = $jpg1x  . ' 1x' . ($jpg2x  !== $jpg1x  && $jpg2x  !== '' ? ', ' . $jpg2x  . ' 2x' : '');

                $attrs['src']    = $jpg1x;
                $attrs['srcset'] = $jpgSrcset;

                return sprintf(
                    '<picture><source type="image/webp" srcset="%s"><img%s></picture>',
                    htmlspecialchars($webpSrcset, ENT_QUOTES, 'UTF-8'),
                    $this->renderAttributes($attrs)
                );
            },
            $html
        );
    }

    private function mediaUrl(string $relativePath): string
    {
        try {
            $base = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $base . ltrim($relativePath, '/');
        } catch (\Throwable) {
            return '';
        }
    }

    private function processIframes(string $html): string
    {
        return (string)preg_replace_callback(
            '/<iframe\b([^>]*)>/i',
            function (array $m): string {
                $attrs = $this->parseAttributes($m[1]);
                if (!isset($attrs['loading'])) {
                    $attrs['loading'] = 'lazy';
                }
                $src = (string)($attrs['src'] ?? '');
                if ($this->isVideoEmbed($src)) {
                    if (!isset($attrs['allow'])) {
                        $attrs['allow'] = 'accelerometer; encrypted-media; gyroscope; picture-in-picture';
                    }
                    if (!isset($attrs['allowfullscreen'])) {
                        $attrs['allowfullscreen'] = '';
                    }
                    if (!isset($attrs['referrerpolicy'])) {
                        $attrs['referrerpolicy'] = 'strict-origin-when-cross-origin';
                    }
                }
                return '<iframe' . $this->renderAttributes($attrs) . '>';
            },
            $html
        );
    }

    private function processYoutubeShortcodes(string $html): string
    {
        return (string)preg_replace_callback(
            self::YOUTUBE_SHORTCODE,
            function (array $m): string {
                $videoId = $m[1];
                $thumb = sprintf('https://i.ytimg.com/vi/%s/maxresdefault.jpg', $videoId);
                $watch = sprintf('https://www.youtube.com/watch?v=%s', $videoId);
                return sprintf(
                    '<a href="%s" class="slova-blog-yt" target="_blank" rel="noopener noreferrer" '
                    . 'aria-label="Vezi pe YouTube"><img src="%s" alt="YouTube" loading="lazy" '
                    . 'decoding="async" width="1280" height="720"><span class="slova-blog-yt__btn" aria-hidden="true">▶</span></a>',
                    htmlspecialchars($watch, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8')
                );
            },
            $html
        );
    }

    private function isVideoEmbed(string $url): bool
    {
        if ($url === '') {
            return false;
        }
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        return str_contains($host, 'youtube.com')
            || str_contains($host, 'youtube-nocookie.com')
            || str_contains($host, 'vimeo.com');
    }

    private function urlToMediaPath(string $url): ?string
    {
        if ($url === '') {
            return null;
        }
        try {
            $baseMedia = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (\Throwable) {
            return null;
        }

        if (str_starts_with($url, $baseMedia)) {
            return substr($url, strlen($baseMedia));
        }

        $parsed = parse_url($url);
        if (!isset($parsed['path'])) {
            return null;
        }
        $path = $parsed['path'];
        $pos = strpos($path, '/media/');
        if ($pos !== false) {
            return ltrim(substr($path, $pos + 7), '/');
        }
        return null;
    }

    /**
     * @return array<string,string>
     */
    private function parseAttributes(string $raw): array
    {
        $attrs = [];
        preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/', $raw, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $name = strtolower($m[1]);
            $value = $m[2] ?? ($m[3] ?? ($m[4] ?? ''));
            $attrs[$name] = $value;
        }
        return $attrs;
    }

    /**
     * @param array<string,string> $attrs
     */
    private function renderAttributes(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $name => $value) {
            if ($value === '' && in_array($name, ['allowfullscreen', 'async', 'defer', 'autoplay', 'controls', 'loop', 'muted'], true)) {
                $out .= ' ' . $name;
            } else {
                $out .= ' ' . $name . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return $out;
    }
}
