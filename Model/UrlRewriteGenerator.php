<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use Slova\Blog\Api\Data\CategoryInterface;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;

/**
 * Keeps the url_rewrite rows of the blog home, posts and categories in line with
 * each store view's URL prefixes.
 */
class UrlRewriteGenerator
{
    public const ENTITY_TYPE_INDEX = 'slova_blog_index';

    /**
     * @param UrlPersistInterface $urlPersist
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param StoreManagerInterface $storeManager
     * @param Url $urlHelper
     * @param PostCollectionFactory $postCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        private readonly UrlPersistInterface $urlPersist,
        private readonly UrlRewriteFactory $urlRewriteFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Url $urlHelper,
        private readonly PostCollectionFactory $postCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory
    ) {
    }

    /**
     * Rewrite the URL of one post in every store view.
     *
     * @param int $postId
     * @param string $urlKey
     * @return void
     */
    public function generateForPost(int $postId, string $urlKey): void
    {
        $this->replaceForEntity(
            PostInterface::ENTITY_TYPE,
            $postId,
            fn (int $storeId): string => $this->urlHelper->getPostRequestPath($urlKey, $storeId),
            $this->urlHelper->getPostTargetPath($postId)
        );
    }

    /**
     * Rewrite the URL of one category in every store view.
     *
     * @param int $categoryId
     * @param string $urlKey
     * @return void
     */
    public function generateForCategory(int $categoryId, string $urlKey): void
    {
        $this->replaceForEntity(
            CategoryInterface::ENTITY_TYPE,
            $categoryId,
            fn (int $storeId): string => $this->urlHelper->getCategoryRequestPath($urlKey, $storeId),
            $this->urlHelper->getCategoryTargetPath($categoryId)
        );
    }

    /**
     * Rewrite the blog home URL in every store view whose prefix differs from the front name.
     *
     * @return void
     */
    public function generateForIndex(): void
    {
        $this->replaceForEntity(
            self::ENTITY_TYPE_INDEX,
            0,
            fn (int $storeId): string => $this->urlHelper->getIndexRequestPath($storeId),
            $this->urlHelper->getIndexTargetPath()
        );
    }

    /**
     * Rebuild every blog URL, e.g. after a URL prefix changed or a store view was added.
     *
     * @return array{posts:int,categories:int}
     */
    public function regenerateAll(): array
    {
        $this->generateForIndex();

        $posts = 0;
        foreach ($this->postCollectionFactory->create() as $post) {
            if ((string)$post->getUrlKey() !== '') {
                $this->generateForPost((int)$post->getId(), (string)$post->getUrlKey());
                $posts++;
            }
        }

        $categories = 0;
        foreach ($this->categoryCollectionFactory->create() as $category) {
            if ((string)$category->getUrlKey() !== '') {
                $this->generateForCategory((int)$category->getId(), (string)$category->getUrlKey());
                $categories++;
            }
        }

        return ['posts' => $posts, 'categories' => $categories];
    }

    /**
     * Delete the URLs of a post.
     *
     * @param int $postId
     * @return void
     */
    public function deleteForPost(int $postId): void
    {
        $this->deleteForEntity(PostInterface::ENTITY_TYPE, $postId);
    }

    /**
     * Delete the URLs of a category.
     *
     * @param int $categoryId
     * @return void
     */
    public function deleteForCategory(int $categoryId): void
    {
        $this->deleteForEntity(CategoryInterface::ENTITY_TYPE, $categoryId);
    }

    /**
     * Replace an entity's rewrites with one per store view.
     *
     * A store view whose request path equals the target needs no rewrite (the blog home
     * with the default prefix), so none is written there.
     *
     * @param string $entityType
     * @param int $entityId
     * @param callable(int):string $requestPathForStore
     * @param string $targetPath
     * @return void
     */
    private function replaceForEntity(
        string $entityType,
        int $entityId,
        callable $requestPathForStore,
        string $targetPath
    ): void {
        $rewrites = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            $requestPath = $requestPathForStore($storeId);
            if ($requestPath === $targetPath) {
                continue;
            }
            /** @var UrlRewrite $rewrite */
            $rewrite = $this->urlRewriteFactory->create();
            $rewrite->setEntityType($entityType)
                ->setEntityId($entityId)
                ->setRequestPath($requestPath)
                ->setTargetPath($targetPath)
                ->setRedirectType(0)
                ->setStoreId($storeId)
                ->setIsAutogenerated(1);
            $rewrites[] = $rewrite;
        }

        $this->deleteForEntity($entityType, $entityId);

        if ($rewrites) {
            $this->urlPersist->replace($rewrites);
        }
    }

    /**
     * Delete all rewrites of an entity.
     *
     * @param string $entityType
     * @param int $entityId
     * @return void
     */
    private function deleteForEntity(string $entityType, int $entityId): void
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_ID => $entityId,
            UrlRewrite::ENTITY_TYPE => $entityType,
        ]);
    }
}
