<?php
declare(strict_types=1);

namespace Slova\Blog\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'category_id';

    protected function _construct(): void
    {
        $this->_init(
            \Slova\Blog\Model\Category::class,
            \Slova\Blog\Model\ResourceModel\Category::class
        );
    }

    public function addActiveFilter(): self
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function applyDefaultOrder(): self
    {
        $this->setOrder('sort_order', 'ASC')->setOrder('name', 'ASC');
        return $this;
    }
}
