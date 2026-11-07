<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Test\Unit\Service;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Slova\Blog\Model\Image\Resizer;
use Slova\Blog\Service\ContentProcessor;

class ContentProcessorTest extends TestCase
{
    private const MEDIA = 'https://shop.test/media/';

    private function processor(bool $resize): ContentProcessor
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->with(UrlInterface::URL_TYPE_MEDIA)->willReturn(self::MEDIA);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $resizer = $this->createMock(Resizer::class);
        $resizer->method('isEnabled')->willReturn($resize);
        $resizer->method('dimensionsAt')->willReturn([900, 600]);
        $resizer->method('mediaUrl')->willReturnCallback(fn (string $p) => self::MEDIA . $p);
        $resizer->method('process')->willReturnCallback(
            fn (string $p, int $w, string $f) => self::MEDIA . "resized/$f/$w/" . preg_replace('/\.\w+$/', ".$f", $p)
        );
        return new ContentProcessor($resizer, $storeManager);
    }

    public function testMediaImageBecomesPicture(): void
    {
        $html = $this->processor(true)->process('<p><img src="' . self::MEDIA . 'slova/blog/a.jpg" alt="A &amp; B"></p>');
        $this->assertStringContainsString('<picture><source type="image/webp" srcset="' . self::MEDIA . 'resized/webp/900/slova/blog/a.webp 1x, ', $html);
        $this->assertStringContainsString('src="' . self::MEDIA . 'resized/jpg/900/slova/blog/a.jpg"', $html);
        $this->assertStringContainsString('width="900" height="600"', $html);
        $this->assertStringContainsString('loading="lazy" decoding="async"', $html);
        $this->assertStringContainsString('alt="A &amp; B"', $html, 'entities are not double-escaped');
    }

    public function testRelativeMediaUrlIsRecognised(): void
    {
        $html = $this->processor(true)->process('<img src="/media/slova/blog/b.png">');
        $this->assertStringContainsString('<picture>', $html);
    }

    public function testExternalImageOnlyGetsLazyLoading(): void
    {
        $html = $this->processor(true)->process('<img src="https://cdn.example.com/x.jpg" alt="x">');
        $this->assertSame('<img src="https://cdn.example.com/x.jpg" alt="x" loading="lazy" decoding="async">', $html);
    }

    public function testResizingOffKeepsTheImgWithSize(): void
    {
        $html = $this->processor(false)->process('<img src="' . self::MEDIA . 'slova/blog/a.jpg">');
        $this->assertStringNotContainsString('<picture>', $html);
        $this->assertStringContainsString('width="900" height="600"', $html);
    }

    public function testYoutubeShortcodeAndIframe(): void
    {
        $html = $this->processor(true)->process('[youtube:dQw4w9WgXcQ]<iframe src="https://www.youtube.com/embed/x"></iframe>');
        $this->assertStringContainsString('href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"', $html);
        $this->assertStringContainsString('https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', $html);
        $this->assertStringContainsString('<iframe src="https://www.youtube.com/embed/x" loading="lazy" allow=', $html);
        $this->assertStringContainsString(' allowfullscreen ', $html);
    }

    public function testMediaImagePathsLeaveOutExternalImages(): void
    {
        $paths = $this->processor(true)->getMediaImagePaths(
            '<img src="' . self::MEDIA . 'slova/blog/a.jpg"><img src="/media/wysiwyg/b.png">'
            . '<img src="https://cdn.example.com/c.jpg"><img src="' . self::MEDIA . 'slova/blog/a.jpg">'
        );
        $this->assertSame(['slova/blog/a.jpg', 'wysiwyg/b.png'], $paths);
    }

    public function testEmptyContent(): void
    {
        $this->assertSame('', $this->processor(true)->process('   '));
    }
}
