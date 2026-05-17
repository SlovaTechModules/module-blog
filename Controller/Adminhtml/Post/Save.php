<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Slova\Blog\Controller\Adminhtml\Post;
use Slova\Blog\Model\ImageUploader;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\ResourceModel\Post as PostResource;
use Slova\Core\Helper\UrlKey;

class Save extends Post
{
    private const IMAGE_FIELDS = ['featured_image', 'og_image', 'side_image', 'thumbnail_mobile'];

    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly ImageUploader $imageUploader,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly UrlKey $urlKeyHelper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('post_id');
        $model = $this->postFactory->create();
        if ($id) {
            $this->postResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Articolul nu există.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->normalizeImages($data);
        $data = $this->normalizeUrlKey($data);
        $data = $this->normalizePublishedAt($data);
        $data = $this->normalizeProducts($data);
        $data = $this->normalizeCategories($data);

        if (empty($data['post_id'])) {
            unset($data['post_id']);
        }

        $model->setData($data);

        try {
            $this->postResource->save($model);
            $this->messageManager->addSuccessMessage(__('Articolul a fost salvat.'));
            $this->dataPersistor->clear('slova_blog_post');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['post_id' => (int)$model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Eroare la salvare: %1', $e->getMessage()));
        }

        $this->dataPersistor->set('slova_blog_post', $data);
        return $resultRedirect->setPath('*/*/edit', ['post_id' => $id]);
    }

    private function normalizeImages(array $data): array
    {
        foreach (self::IMAGE_FIELDS as $field) {
            if (isset($data[$field][0]['name']) && !isset($data[$field][0]['tmp_name'])) {
                $data[$field] = $data[$field][0]['name'];
            } elseif (isset($data[$field][0]['name'])) {
                $fileName = $data[$field][0]['name'];
                try {
                    $this->imageUploader->moveFileFromTmp($fileName);
                } catch (\Exception $e) {
                    // already moved
                }
                $data[$field] = $fileName;
            }
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }
        return $data;
    }

    private function normalizeUrlKey(array $data): array
    {
        if (empty($data['url_key']) && !empty($data['title'])) {
            $data['url_key'] = $this->urlKeyHelper->normalizeUrlKey((string)$data['title']);
        } elseif (!empty($data['url_key'])) {
            $data['url_key'] = $this->urlKeyHelper->normalizeUrlKey((string)$data['url_key']);
        }
        return $data;
    }

    private function normalizePublishedAt(array $data): array
    {
        if (empty($data['published_at'])) {
            $data['published_at'] = null;
        }
        return $data;
    }

    private function normalizeCategories(array $data): array
    {
        if (isset($data['category_ids']) && is_string($data['category_ids'])) {
            $data['category_ids'] = array_filter(array_map('intval', explode(',', $data['category_ids'])));
        }
        $data['category_ids'] = isset($data['category_ids']) ? array_values((array)$data['category_ids']) : [];
        return $data;
    }

    private function normalizeProducts(array $data): array
    {
        $skuString = trim((string)($data['related_skus'] ?? ''));
        unset($data['related_skus']);

        if ($skuString === '') {
            $data['product_ids'] = [];
            return $data;
        }

        $skus = array_filter(array_map('trim', preg_split('/[,\s]+/', $skuString) ?: []));
        if (!$skus) {
            $data['product_ids'] = [];
            return $data;
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->create();
        $products = $this->productRepository->getList($criteria)->getItems();

        $foundSkus = [];
        $productIds = [];
        foreach ($products as $product) {
            $productIds[] = (int)$product->getId();
            $foundSkus[] = strtolower((string)$product->getSku());
        }

        $missing = array_diff(array_map('strtolower', $skus), $foundSkus);
        if ($missing) {
            $this->messageManager->addWarningMessage(
                __('SKU-uri negăsite (ignorate): %1', implode(', ', $missing))
            );
        }

        $data['product_ids'] = $productIds;
        return $data;
    }
}
