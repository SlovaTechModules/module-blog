<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Sitemap;

use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;
use Slova\Blog\Model\Url;

/**
 * Blog home and published posts in the XML sitemap (Marketing > SEO & Search > Site Map).
 */
class PostItemProvider implements ItemProviderInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param SitemapItemInterfaceFactory $itemFactory
     * @param Url $url
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly SitemapItemInterfaceFactory $itemFactory,
        private readonly Url $url
    ) {
    }

    /**
     * @inheritDoc
     *
     * @return SitemapItemInterface[]
     */
    public function getItems($storeId): array
    {
        $storeId = (int)$storeId;
        $collection = $this->collectionFactory->create()
            ->addActiveFilter()
            ->addPublishedFilter()
            ->applyDefaultOrder();

        $items = [];
        $latest = null;
        foreach ($collection as $post) {
            $updatedAt = (string)($post->getUpdatedAt() ?: $post->getCreatedAt());
            $latest ??= $updatedAt;
            $items[] = $this->itemFactory->create([
                'url' => $this->url->getPostRequestPath((string)$post->getUrlKey(), $storeId),
                'updatedAt' => $updatedAt,
                'images' => null,
                'priority' => '0.6',
                'changeFrequency' => 'monthly',
            ]);
        }

        array_unshift($items, $this->itemFactory->create([
            'url' => $this->url->getIndexRequestPath($storeId),
            'updatedAt' => $latest ?? date('Y-m-d H:i:s'),
            'images' => null,
            'priority' => '0.5',
            'changeFrequency' => 'daily',
        ]));

        return $items;
    }
}
