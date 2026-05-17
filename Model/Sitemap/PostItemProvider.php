<?php
declare(strict_types=1);

namespace Slova\Blog\Model\Sitemap;

use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;

class PostItemProvider implements ItemProviderInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly SitemapItemInterfaceFactory $itemFactory
    ) {
    }

    /**
     * @return SitemapItemInterface[]
     */
    public function getItems($storeId): array
    {
        $collection = $this->collectionFactory->create()
            ->addActiveFilter()
            ->addPublishedFilter()
            ->applyDefaultOrder();

        $items = [];
        foreach ($collection as $post) {
            $items[] = $this->itemFactory->create([
                'url'             => 'jurnal-de-bord/' . $post->getUrlKey(),
                'updatedAt'       => (string)($post->getUpdatedAt() ?: $post->getCreatedAt()),
                'images'          => null,
                'priority'        => '0.6',
                'changeFrequency' => 'monthly',
            ]);
        }
        return $items;
    }
}
