<?php
declare(strict_types=1);

namespace Slova\Blog\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Api\Data\CategoryInterface;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Model\Category;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Slova\Blog\Model\ResourceModel\Post\Collection;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;
use Slova\Blog\Model\Url as BlogUrl;

class PostList extends Template implements IdentityInterface
{
    public const PAGE_SIZE = 12;

    private ?Collection $collection = null;
    /** @var Category[]|null */
    private ?array $categoriesById = null;

    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getPosts(): Collection
    {
        if ($this->collection === null) {
            $collection = $this->collectionFactory->create()
                ->addActiveFilter()
                ->addPublishedFilter()
                ->applyDefaultOrder();

            $category = $this->getCategory();
            if ($category) {
                $collection->addCategoryFilter((int)$category->getId());
            }

            $page = max(1, (int)$this->getRequest()->getParam('p', 1));
            $collection->setPageSize(self::PAGE_SIZE)->setCurPage($page);

            $this->collection = $collection;
        }
        return $this->collection;
    }

    public function getCategory(): ?Category
    {
        return $this->registry->registry('current_blog_category');
    }

    public function getPostUrl(string $urlKey): string
    {
        return $this->blogUrl->getPostUrl($urlKey);
    }

    public function getCategoryUrl(string $urlKey): string
    {
        return $this->blogUrl->getCategoryUrl($urlKey);
    }

    /**
     * @return Category[]
     */
    public function getCategoriesForPost(Post $post): array
    {
        $ids = $post->getCategoryIds();
        if (!$ids) {
            return [];
        }
        $this->loadCategoriesById();
        return array_values(array_intersect_key($this->categoriesById, array_flip($ids)));
    }

    private function loadCategoriesById(): void
    {
        if ($this->categoriesById !== null) {
            return;
        }
        $this->categoriesById = [];
        $collection = $this->categoryCollectionFactory->create()->addActiveFilter();
        foreach ($collection as $cat) {
            $this->categoriesById[(int)$cat->getId()] = $cat;
        }
    }

    public function getFeaturedImagePath(Post $post): string
    {
        $img = (string)$post->getFeaturedImage();
        return $img !== '' ? 'slova/blog/' . $img : '';
    }

    public function getFormattedDate(Post $post): string
    {
        $date = $post->getPublishedAt() ?: $post->getCreatedAt();
        if (!$date) {
            return '';
        }
        $months = [
            1 => 'ianuarie', 2 => 'februarie', 3 => 'martie', 4 => 'aprilie',
            5 => 'mai', 6 => 'iunie', 7 => 'iulie', 8 => 'august',
            9 => 'septembrie', 10 => 'octombrie', 11 => 'noiembrie', 12 => 'decembrie',
        ];
        $ts = strtotime($date);
        return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    public function getPagerHtml(): string
    {
        $collection = $this->getPosts();
        $pager = $this->getLayout()->createBlock(\Magento\Theme\Block\Html\Pager::class, 'slova.blog.post.pager')
            ->setLimit(self::PAGE_SIZE)
            ->setShowAmounts(false)
            ->setShowPerPage(false)
            ->setCollection($collection)
            ->setPageVarName('p');
        return $pager->toHtml();
    }

    public function getIdentities(): array
    {
        $tags = [PostInterface::CACHE_TAG];
        foreach ($this->getPosts() as $post) {
            $tags[] = PostInterface::CACHE_TAG . '_' . $post->getId();
        }
        $category = $this->getCategory();
        if ($category) {
            $tags[] = CategoryInterface::CACHE_TAG . '_' . $category->getId();
        }
        return $tags;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $title = $this->getCategory() ? $this->getCategory()->getName() : __('Jurnal de bord');
        $this->pageConfig->getTitle()->set($title);

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Acasă'),
                'title' => __('Acasă'),
                'link'  => $this->_storeManager->getStore()->getBaseUrl(),
            ]);
            $crumbOptions = ['label' => __('Jurnal de bord'), 'title' => __('Jurnal de bord')];
            if ($this->getCategory()) {
                $crumbOptions['link'] = $this->blogUrl->getBaseUrl();
            }
            $breadcrumbs->addCrumb('jurnal', $crumbOptions);
            if ($this->getCategory()) {
                $breadcrumbs->addCrumb('category', [
                    'label' => $this->getCategory()->getName(),
                    'title' => $this->getCategory()->getName(),
                ]);
            }
        }

        return $this;
    }
}
