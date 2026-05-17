<?php
declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Slova\Blog\Api\Data\CategoryInterface;

class Category extends AbstractModel implements IdentityInterface
{
    protected $_eventPrefix = 'slova_blog_category';

    protected function _construct()
    {
        $this->_init(\Slova\Blog\Model\ResourceModel\Category::class);
    }

    public function getIdentities(): array
    {
        return [CategoryInterface::CACHE_TAG . '_' . $this->getId()];
    }
}
