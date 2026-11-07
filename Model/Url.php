<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds the blog's public URLs (from the URL prefix settings) and the internal
 * route paths URL rewrites point to.
 */
class Url
{
    /** Front name in etc/frontend/routes.xml: the target of every blog URL rewrite. */
    public const FRONT_NAME = 'blog';

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config
    ) {
    }

    /**
     * Absolute URL of the blog home.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getBaseUrl(?int $storeId = null): string
    {
        return $this->getStoreBaseUrl($storeId) . $this->getIndexRequestPath($storeId);
    }

    /**
     * Absolute URL of a post.
     *
     * @param string $urlKey
     * @param int|null $storeId
     * @return string
     */
    public function getPostUrl(string $urlKey, ?int $storeId = null): string
    {
        return $this->getStoreBaseUrl($storeId) . $this->getPostRequestPath($urlKey, $storeId);
    }

    /**
     * Absolute URL of a category page.
     *
     * @param string $urlKey
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryUrl(string $urlKey, ?int $storeId = null): string
    {
        return $this->getStoreBaseUrl($storeId) . $this->getCategoryRequestPath($urlKey, $storeId);
    }

    /**
     * Request path of the blog home, e.g. "blog".
     *
     * @param int|null $storeId
     * @return string
     */
    public function getIndexRequestPath(?int $storeId = null): string
    {
        return $this->config->getUrlPrefix($storeId);
    }

    /**
     * Request path of a post, e.g. "blog/my-post".
     *
     * @param string $urlKey
     * @param int|null $storeId
     * @return string
     */
    public function getPostRequestPath(string $urlKey, ?int $storeId = null): string
    {
        return $this->config->getUrlPrefix($storeId) . '/' . $urlKey;
    }

    /**
     * Request path of a category, e.g. "blog/category/news".
     *
     * @param string $urlKey
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryRequestPath(string $urlKey, ?int $storeId = null): string
    {
        return $this->config->getUrlPrefix($storeId) . '/'
            . $this->config->getCategoryUrlPrefix($storeId) . '/' . $urlKey;
    }

    /**
     * Internal route of a post.
     *
     * @param int $postId
     * @return string
     */
    public function getPostTargetPath(int $postId): string
    {
        return self::FRONT_NAME . '/post/view/id/' . $postId;
    }

    /**
     * Internal route of a category page.
     *
     * @param int $categoryId
     * @return string
     */
    public function getCategoryTargetPath(int $categoryId): string
    {
        return self::FRONT_NAME . '/category/view/id/' . $categoryId;
    }

    /**
     * Internal route of the blog home.
     *
     * @return string
     */
    public function getIndexTargetPath(): string
    {
        return self::FRONT_NAME;
    }

    /**
     * Whether the request came in through the given public path, rather than through the
     * internal route or an outdated URL.
     *
     * Both the URL rewrite router and the standard router (for a bare front name such as
     * /blog) record the requested path in the rewrite alias, so the alias is compared
     * with the expected path, not just checked for presence.
     *
     * @param RequestInterface $request
     * @param string $expectedPath
     * @return bool
     */
    public function isPublicRequest(RequestInterface $request, string $expectedPath): bool
    {
        if (!method_exists($request, 'getAlias')) {
            return true;
        }
        $alias = trim((string)$request->getAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS), '/');
        return $alias === trim($expectedPath, '/');
    }

    /**
     * Store base URL with a trailing slash.
     *
     * @param int|null $storeId
     * @return string
     */
    private function getStoreBaseUrl(?int $storeId): string
    {
        return rtrim((string)$this->storeManager->getStore($storeId)->getBaseUrl(), '/') . '/';
    }
}
