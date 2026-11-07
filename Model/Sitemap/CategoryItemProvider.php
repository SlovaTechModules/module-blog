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
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory;
use Slova\Blog\Model\Url;

/**
 * Active blog categories in the XML sitemap.
 */
class CategoryItemProvider implements ItemProviderInterface
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
        $collection = $this->collectionFactory->create()
            ->addActiveFilter()
            ->applyDefaultOrder();

        $items = [];
        foreach ($collection as $category) {
            $items[] = $this->itemFactory->create([
                'url' => $this->url->getCategoryRequestPath((string)$category->getUrlKey(), (int)$storeId),
                'updatedAt' => date('Y-m-d H:i:s'),
                'images' => null,
                'priority' => '0.4',
                'changeFrequency' => 'weekly',
            ]);
        }
        return $items;
    }
}
