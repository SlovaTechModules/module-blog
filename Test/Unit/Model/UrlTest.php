<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Test\Unit\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\Url;

class UrlTest extends TestCase
{
    private function url(string $prefix, string $categoryPrefix): Url
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $config = $this->createMock(Config::class);
        $config->method('getUrlPrefix')->willReturn($prefix);
        $config->method('getCategoryUrlPrefix')->willReturn($categoryPrefix);
        return new Url($storeManager, $config);
    }

    public function testPublicUrlsFollowPrefixes(): void
    {
        $url = $this->url('news/journal', 'topic');
        $this->assertSame('https://shop.test/news/journal', $url->getBaseUrl());
        $this->assertSame('https://shop.test/news/journal/my-post', $url->getPostUrl('my-post'));
        $this->assertSame('https://shop.test/news/journal/topic/tips', $url->getCategoryUrl('tips'));
    }

    public function testTargetsUseTheFrontName(): void
    {
        $url = $this->url('anything', 'cat');
        $this->assertSame('blog/post/view/id/7', $url->getPostTargetPath(7));
        $this->assertSame('blog/category/view/id/3', $url->getCategoryTargetPath(3));
        $this->assertSame('blog', $url->getIndexTargetPath());
    }

    public function testPublicRequestComparesTheAlias(): void
    {
        $url = $this->url('blog', 'category');
        $request = $this->createMock(Http::class);
        $request->method('getAlias')->with(UrlInterface::REWRITE_REQUEST_PATH_ALIAS)
            ->willReturnOnConsecutiveCalls('blog/my-post', '', 'blog/old-key', 'blog/');

        $this->assertTrue($url->isPublicRequest($request, 'blog/my-post'));
        $this->assertFalse($url->isPublicRequest($request, 'blog/my-post'), 'internal route');
        $this->assertFalse($url->isPublicRequest($request, 'blog/my-post'), 'outdated URL key');
        $this->assertTrue($url->isPublicRequest($request, 'blog'), 'trailing slash');
    }
}
