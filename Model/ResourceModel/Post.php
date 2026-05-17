<?php
declare(strict_types=1);

namespace Slova\Blog\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Slova\Blog\Model\UrlRewriteGenerator;

class Post extends AbstractDb
{
    public function __construct(
        Context $context,
        private readonly UrlRewriteGenerator $urlRewriteGenerator,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    protected function _construct(): void
    {
        $this->_init('slova_blog_post', 'post_id');
    }

    protected function _afterSave(AbstractModel $object)
    {
        $result = parent::_afterSave($object);
        $postId = (int)$object->getId();

        if ($object->hasData('category_ids')) {
            $this->saveCategoryLinks($postId, (array)$object->getData('category_ids'));
        }

        if ($object->hasData('product_ids')) {
            $this->saveProductLinks($postId, (array)$object->getData('product_ids'));
        }

        if ($object->getUrlKey()) {
            $this->urlRewriteGenerator->generateForPost($postId, (string)$object->getUrlKey());
        }

        return $result;
    }

    protected function _afterDelete(AbstractModel $object)
    {
        $this->urlRewriteGenerator->deleteForPost((int)$object->getId());
        return parent::_afterDelete($object);
    }

    public function getCategoryIds(int $postId): array
    {
        $connection = $this->getConnection();
        return array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from($this->getTable('slova_blog_post_category'), ['category_id'])
                ->where('post_id = ?', $postId)
        ));
    }

    public function getProductIds(int $postId): array
    {
        $connection = $this->getConnection();
        return array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from($this->getTable('slova_blog_post_product'), ['product_id'])
                ->where('post_id = ?', $postId)
                ->order('position ASC')
        ));
    }

    private function saveCategoryLinks(int $postId, array $categoryIds): void
    {
        $table = $this->getTable('slova_blog_post_category');
        $connection = $this->getConnection();
        $connection->delete($table, ['post_id = ?' => $postId]);

        $rows = [];
        foreach (array_unique(array_filter(array_map('intval', $categoryIds))) as $categoryId) {
            $rows[] = ['post_id' => $postId, 'category_id' => $categoryId];
        }
        if ($rows) {
            $connection->insertMultiple($table, $rows);
        }
    }

    private function saveProductLinks(int $postId, array $productIds): void
    {
        $table = $this->getTable('slova_blog_post_product');
        $connection = $this->getConnection();
        $connection->delete($table, ['post_id = ?' => $postId]);

        $rows = [];
        $position = 0;
        foreach (array_unique(array_filter(array_map('intval', $productIds))) as $productId) {
            $rows[] = ['post_id' => $postId, 'product_id' => $productId, 'position' => $position++];
        }
        if ($rows) {
            $connection->insertMultiple($table, $rows);
        }
    }
}
