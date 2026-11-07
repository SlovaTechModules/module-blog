<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Slova\Blog\Model\UrlKeyGenerator;

class UrlKeyGeneratorTest extends TestCase
{
    /**
     * @dataProvider keys
     */
    public function testGenerate(string $text, string $expected): void
    {
        $this->assertSame($expected, (new UrlKeyGenerator())->generate($text));
    }

    public static function keys(): array
    {
        return [
            'romanian comma-below' => ['Știri și noutăți!', 'stiri-si-noutati'],
            'romanian cedilla' => ['Şcoala ţării', 'scoala-tarii'],
            'german' => ['Größe & Übermaß', 'grosse-ubermass'],
            'spaces and punctuation' => ['  Hello,   World -- 2026  ', 'hello-world-2026'],
            'already a key' => ['my-post', 'my-post'],
            'empty' => ['   ', ''],
            'nothing usable' => ['!!!', ''],
        ];
    }

    public function testGeneratePathKeepsLevels(): void
    {
        $this->assertSame('news/blog', (new UrlKeyGenerator())->generatePath(' /News/ Blog/ '));
        $this->assertSame('jurnal-de-bord', (new UrlKeyGenerator())->generatePath('Jurnal de bord'));
        $this->assertSame('', (new UrlKeyGenerator())->generatePath('/!!/'));
    }
}
