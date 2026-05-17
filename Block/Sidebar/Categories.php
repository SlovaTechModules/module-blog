<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Sidebar;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Api\Data\CategoryInterface;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Model\Category;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\ResourceModel\Category\Collection;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory;
use Slova\Blog\Model\Url as BlogUrl;

class Categories extends Template implements IdentityInterface
{
    private ?Collection $collection = null;
    /** @var array<int,int>|null */
    private ?array $counts = null;

    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryResource $categoryResource,
        private readonly BlogUrl $blogUrl,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getPostCount(Category $category): int
    {
        if ($this->counts === null) {
            $this->counts = $this->categoryResource->getPostCounts();
        }
        return $this->counts[(int)$category->getId()] ?? 0;
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        if ($this->collection === null) {
            $this->collection = $this->collectionFactory->create()
                ->addActiveFilter()
                ->applyDefaultOrder();
        }
        return array_values(iterator_to_array($this->collection->getIterator()));
    }

    public function getCategoryUrl(Category $category): string
    {
        return $this->blogUrl->getCategoryUrl((string)$category->getUrlKey());
    }

    public function isCurrent(Category $category): bool
    {
        $current = $this->registry->registry('current_blog_category');
        return $current && (int)$current->getId() === (int)$category->getId();
    }

    public function getIdentities(): array
    {
        $tags = [CategoryInterface::CACHE_TAG, PostInterface::CACHE_TAG];
        foreach ($this->getCategories() as $cat) {
            $tags[] = CategoryInterface::CACHE_TAG . '_' . $cat->getId();
        }
        return $tags;
    }
}
