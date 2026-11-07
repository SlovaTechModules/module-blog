<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

/**
 * Turns the plain-text intro of a post into safe HTML: everything is escaped except
 * <strong> and <em>; a blank line starts a new paragraph, a single line break stays a <br>.
 */
class SideTextFormatter
{
    private const ALLOWED_TAGS = [
        '&lt;strong&gt;' => '<strong>',
        '&lt;/strong&gt;' => '</strong>',
        '&lt;em&gt;' => '<em>',
        '&lt;/em&gt;' => '</em>',
    ];

    /**
     * Format the intro text.
     *
     * @param string $text
     * @return string
     */
    public function format(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }

        $html = strtr(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), self::ALLOWED_TAGS);

        $paragraphs = [];
        foreach (preg_split('/\n\s*\n/', $html) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $paragraphs[] = '<p>' . nl2br($paragraph, false) . '</p>';
            }
        }
        return implode('', $paragraphs);
    }
}
