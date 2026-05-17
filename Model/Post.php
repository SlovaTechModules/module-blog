<?php
declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Slova\Blog\Api\Data\PostInterface;

class Post extends AbstractModel implements IdentityInterface
{
    protected $_eventPrefix = 'slova_blog_post';

    protected function _construct()
    {
        $this->_init(\Slova\Blog\Model\ResourceModel\Post::class);
    }

    public function getIdentities(): array
    {
        return [PostInterface::CACHE_TAG . '_' . $this->getId()];
    }

    public function getCategoryIds(): array
    {
        if (!$this->hasData('category_ids')) {
            $ids = $this->getResource()->getCategoryIds((int)$this->getId());
            $this->setData('category_ids', $ids);
        }
        return (array)$this->getData('category_ids');
    }

    public function getProductIds(): array
    {
        if (!$this->hasData('product_ids')) {
            $ids = $this->getResource()->getProductIds((int)$this->getId());
            $this->setData('product_ids', $ids);
        }
        return (array)$this->getData('product_ids');
    }
}
