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
        return $connection->fetchCol(
            $connection->select()
                ->from($this->getTable('slova_blog_post_category'), ['category_id'])
                ->where('post_id = ?', $postId)
        );
    }

    public function getProductIds(int $postId): array
    {
        $connection = $this->getConnection();
        return $connection->fetchCol(
            $connection->select()
                ->from($this->getTable('slova_blog_post_product'), ['product_id'])
                ->where('post_id = ?', $postId)
                ->order('position ASC')
        );
    }

    /**
     * Sibling post in the same category, ordered by effective publish date.
     * @param string $direction 'prev' = older, 'next' = newer
     * @return array{post_id:int,url_key:string,title:string}|null
     */
    public function getNeighborInCategory(int $postId, int $categoryId, string $direction): ?array
    {
        $connection = $this->getConnection();
        $postTable = $this->getTable('slova_blog_post');

        $current = $connection->fetchRow(
            $connection->select()
                ->from($postTable, ['published_at', 'created_at'])
                ->where('post_id = ?', $postId)
        );
        if (!$current) {
            return null;
        }
        $effectiveDate = $current['published_at'] ?: $current['created_at'];

        $op = $direction === 'prev' ? '<' : '>';
        $order = $direction === 'prev' ? 'DESC' : 'ASC';

        $select = $connection->select()
            ->from(['p' => $postTable], ['post_id', 'url_key', 'title'])
            ->join(
                ['c' => $this->getTable('slova_blog_post_category')],
                'c.post_id = p.post_id',
                []
            )
            ->where('c.category_id = ?', $categoryId)
            ->where('p.is_active = ?', 1)
            ->where('p.published_at IS NULL OR p.published_at <= ?', date('Y-m-d H:i:s'))
            ->where("COALESCE(p.published_at, p.created_at) {$op} ?", $effectiveDate)
            ->where('p.post_id != ?', $postId)
            ->order("COALESCE(p.published_at, p.created_at) {$order}")
            ->limit(1);

        $row = $connection->fetchRow($select);
        if (!$row) {
            return null;
        }
        return [
            'post_id' => (int)$row['post_id'],
            'url_key' => (string)$row['url_key'],
            'title'   => (string)$row['title'],
        ];
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
