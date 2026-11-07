<?php
declare(strict_types=1);

namespace Slova\Blog\Model\Post;

use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private const IMAGE_FIELDS_BY_FIELDSET = [
        'general' => ['featured_image', 'thumbnail_mobile'],
        'intro'   => ['side_image'],
        'seo'     => ['og_image'],
    ];

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
        private readonly BackendUrlInterface $backendUrl,
        private readonly Filesystem $filesystem,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();
        $galleryConfig = [
            'mediaGallery' => [
                'openDialogUrl'   => $this->backendUrl->getUrl('cms/wysiwyg_images/index'),
                'openDialogTitle' => (string) __('Choose Image'),
                'storeId'         => (int) $this->storeManager->getStore()->getId(),
            ],
        ];

        foreach (self::IMAGE_FIELDS_BY_FIELDSET as $fieldset => $fields) {
            foreach ($fields as $field) {
                if (isset($meta[$fieldset]['children'][$field])) {
                    $existing = $meta[$fieldset]['children'][$field]['arguments']['data']['config'] ?? [];
                    $meta[$fieldset]['children'][$field]['arguments']['data']['config'] = array_merge(
                        $existing,
                        $galleryConfig
                    );
                }
            }
        }

        return $meta;
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
                'is_active'           => 1,
                'show_featured_image' => 1,
                'meta_robots'         => 'INDEX,FOLLOW',
                'category_ids'        => [],
            ];
        }

        foreach ($items as $post) {
            $data = $post->getData();
            $id = (int)$post->getData('post_id');

            foreach (['featured_image', 'og_image', 'side_image', 'thumbnail_mobile'] as $field) {
                if (!empty($data[$field]) && !is_array($data[$field])) {
                    $value = (string)$data[$field];
                    $relativePath = str_contains($value, '/') ? ltrim($value, '/') : 'slova/blog/' . $value;
                    $data[$field] = [array_merge(
                        ['name' => $value, 'url' => $mediaUrl . $relativePath],
                        $this->getFileInfo($relativePath)
                    )];
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

    /**
     * Size and MIME type of a media file, so the image uploader shows "1080x460, 120 KB"
     * instead of "NaN undefined" for images saved earlier.
     *
     * @param string $relativePath
     * @return array{size?:int,type?:string}
     */
    private function getFileInfo(string $relativePath): array
    {
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$media->isFile($relativePath)) {
            return [];
        }
        $info = $media->stat($relativePath);
        $absolute = $media->getAbsolutePath($relativePath);
        $type = function_exists('mime_content_type') ? (string)mime_content_type($absolute) : '';
        return array_filter(['size' => (int)($info['size'] ?? 0), 'type' => $type]);
    }

    private function productIdsToSkus(array $productIds): string
    {
        if (!$productIds) {
            return '';
        }
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $skuById = [];
        foreach ($this->productRepository->getList($criteria)->getItems() as $product) {
            $skuById[(int)$product->getId()] = (string)$product->getSku();
        }
        // In the saved order, so saving again keeps the slider order.
        $skus = [];
        foreach ($productIds as $id) {
            if (isset($skuById[(int)$id])) {
                $skus[] = $skuById[(int)$id];
            }
        }
        return implode(', ', $skus);
    }
}
