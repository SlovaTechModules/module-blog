<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Slova\Blog\Model\SideTextFormatter;

class SideTextFormatterTest extends TestCase
{
    public function testKeepsStrongAndEmOnly(): void
    {
        $html = (new SideTextFormatter())->format('A <strong>bold</strong> and <em>soft</em> <b>x</b> <script>alert(1)</script>');
        $this->assertSame(
            '<p>A <strong>bold</strong> and <em>soft</em> &lt;b&gt;x&lt;/b&gt; &lt;script&gt;alert(1)&lt;/script&gt;</p>',
            $html
        );
    }

    public function testParagraphsAndLineBreaks(): void
    {
        $html = (new SideTextFormatter())->format("First line\r\nsecond line\n\n\nNew paragraph");
        $this->assertSame('<p>First line<br>' . "\n" . 'second line</p><p>New paragraph</p>', $html);
    }

    public function testEmpty(): void
    {
        $this->assertSame('', (new SideTextFormatter())->format("  \n "));
    }

    public function testAttributesOnAllowedTagsAreNotKept(): void
    {
        $html = (new SideTextFormatter())->format('<strong onclick="x()">no</strong>');
        $this->assertStringNotContainsString('<strong onclick', $html);
    }
}
