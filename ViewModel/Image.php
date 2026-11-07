<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\ViewModel;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Slova\Blog\Model\Image\Resizer;

/**
 * Renders a responsive <picture>: WebP source with 1x/2x copies and a JPEG <img>
 * fallback carrying width and height, so the browser reserves the space (no layout shift).
 */
class Image implements ArgumentInterface
{
    /**
     * @param Resizer $resizer
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly Resizer $resizer,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * Picture element for a media image shown $width CSS pixels wide.
     *
     * @param string $sourcePath path relative to pub/media
     * @param int $width
     * @param string $alt
     * @param string $loading lazy|eager
     * @param string $fetchPriority auto|high|low
     * @param string $imgClass
     * @return string
     */
    public function picture(
        string $sourcePath,
        int $width,
        string $alt = '',
        string $loading = 'lazy',
        string $fetchPriority = 'auto',
        string $imgClass = ''
    ): string {
        if ($sourcePath === '' || $width <= 0) {
            return '';
        }

        [$w, $h] = $this->resizer->dimensionsAt($sourcePath, $width);
        $img = $this->renderImg([
            'src' => $this->resizer->process($sourcePath, $width, 'jpg'),
            'srcset' => $this->srcset($sourcePath, $width, 'jpg'),
            'alt' => $alt,
            'width' => $w > 0 ? (string)$w : null,
            'height' => $h > 0 ? (string)$h : null,
            'loading' => $loading,
            'decoding' => $loading === 'lazy' ? 'async' : null,
            'fetchpriority' => $fetchPriority !== 'auto' ? $fetchPriority : null,
            'class' => $imgClass !== '' ? $imgClass : null,
        ]);

        if (!$this->resizer->isEnabled()) {
            return $img;
        }

        return sprintf(
            '<picture><source type="image/webp" srcset="%s">%s</picture>',
            $this->escaper->escapeHtml($this->srcset($sourcePath, $width, 'webp')),
            $img
        );
    }

    /**
     * URL of one resized copy.
     *
     * @param string $sourcePath
     * @param int $width
     * @param string $format
     * @return string
     */
    public function url(string $sourcePath, int $width, string $format = 'webp'): string
    {
        return $this->resizer->process($sourcePath, $width, $format);
    }

    /**
     * "url 1x, url 2x" for one format; a single URL when both are the same file.
     *
     * @param string $sourcePath
     * @param int $width
     * @param string $format
     * @return string
     */
    public function srcset(string $sourcePath, int $width, string $format): string
    {
        $x1 = $this->resizer->process($sourcePath, $width, $format);
        $x2 = $this->resizer->process($sourcePath, $width * 2, $format);
        return $x2 !== '' && $x2 !== $x1 ? $x1 . ' 1x, ' . $x2 . ' 2x' : $x1;
    }

    /**
     * Render an <img>, skipping null attributes.
     *
     * @param array<string,string|null> $attributes
     * @return string
     */
    private function renderImg(array $attributes): string
    {
        $html = '<img';
        foreach ($attributes as $name => $value) {
            if ($value === null) {
                continue;
            }
            // Quoted attribute: escaping &, <, >, " and ' is enough, and keeps URLs readable.
            $html .= ' ' . $name . '="' . $this->escaper->escapeHtml($value) . '"';
        }
        return $html . '>';
    }
}
