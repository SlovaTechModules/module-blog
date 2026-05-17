<?php
declare(strict_types=1);

namespace Slova\Blog\Model\ResourceModel\Post;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'post_id';

    protected function _construct(): void
    {
        $this->_init(
            \Slova\Blog\Model\Post::class,
            \Slova\Blog\Model\ResourceModel\Post::class
        );
    }

    public function addActiveFilter(): self
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function addPublishedFilter(): self
    {
        $this->getSelect()->where(
            'main_table.published_at IS NULL OR main_table.published_at <= ?',
            date('Y-m-d H:i:s')
        );
        return $this;
    }

    public function addCategoryFilter(int $categoryId): self
    {
        $this->getSelect()->join(
            ['cat_link' => $this->getTable('slova_blog_post_category')],
            'cat_link.post_id = main_table.post_id',
            []
        )->where('cat_link.category_id = ?', $categoryId);
        return $this;
    }

    public function applyDefaultOrder(): self
    {
        $this->getSelect()->order('COALESCE(main_table.published_at, main_table.created_at) DESC');
        return $this;
    }
}
