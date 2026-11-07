<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

/**
 * Turns a title into a URL key: "Știri și noutăți!" → "stiri-si-noutati".
 *
 * Transliterates with ICU (the intl extension Magento already requires), so letters
 * Magento's own URL filter drops, such as Romanian ș and ț with a comma below, become
 * plain ASCII instead of disappearing.
 */
class UrlKeyGenerator
{
    /**
     * Build a URL key from any text.
     *
     * @param string $text
     * @return string
     */
    public function generate(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $converted = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        $text = strtolower($text);
        $text = (string)preg_replace('/[^a-z0-9-]+/', '-', $text);
        $text = (string)preg_replace('/-{2,}/', '-', $text);

        return trim($text, '-');
    }

    /**
     * Normalize a URL path that may have several segments ("News/Blog" → "news/blog").
     *
     * @param string $path
     * @return string
     */
    public function generatePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            $segment = $this->generate($segment);
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }
        return implode('/', $segments);
    }
}
