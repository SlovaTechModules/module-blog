<?php
declare(strict_types=1);

namespace Slova\Blog\Model\Sitemap;

use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory;

class CategoryItemProvider implements ItemProviderInterface
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
            ->applyDefaultOrder();

        $items = [];
        foreach ($collection as $category) {
            $items[] = $this->itemFactory->create([
                'url'             => 'jurnal-de-bord/categorie/' . $category->getUrlKey(),
                'updatedAt'       => date('Y-m-d H:i:s'),
                'images'          => null,
                'priority'        => '0.4',
                'changeFrequency' => 'weekly',
            ]);
        }
        return $items;
    }
}
