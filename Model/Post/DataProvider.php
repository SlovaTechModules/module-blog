<?php
declare(strict_types=1);

namespace Slova\Blog\Model\Post;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        if (!$items) {
            $this->loadedData[''] = [
                'is_active'   => 1,
                'meta_robots' => 'INDEX,FOLLOW',
            ];
        }

        foreach ($items as $post) {
            $data = $post->getData();
            $id = (int)$post->getData('post_id');

            foreach (['featured_image', 'og_image'] as $field) {
                if (!empty($data[$field]) && !is_array($data[$field])) {
                    $data[$field] = [[
                        'name' => $data[$field],
                        'url'  => $mediaUrl . 'slova/blog/' . $data[$field],
                    ]];
                }
            }

            $data['category_ids'] = $post->getCategoryIds();
            $data['related_skus'] = $this->productIdsToSkus($post->getProductIds());

            $this->loadedData[$id] = $data;
        }

        $persisted = $this->dataPersistor->get('slova_blog_post');
        if (!empty($persisted)) {
            $post = $this->collection->getNewEmptyItem();
            $post->setData($persisted);
            $id = (int)$post->getId();
            $this->loadedData[$id] = $post->getData();
            $this->dataPersistor->clear('slova_blog_post');
        }

        return $this->loadedData;
    }

    private function productIdsToSkus(array $productIds): string
    {
        if (!$productIds) {
            return '';
        }
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $products = $this->productRepository->getList($criteria)->getItems();
        $skus = [];
        foreach ($products as $product) {
            $skus[] = $product->getSku();
        }
        return implode(', ', $skus);
    }
}
