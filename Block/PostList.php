<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Theme\Block\Html\Pager;
use Slova\Blog\Api\Data\CategoryInterface;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Model\Category;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\DateFormatter;
use Slova\Blog\Model\Image\Resizer;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Slova\Blog\Model\ResourceModel\Post\Collection;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;
use Slova\Blog\Model\Url as BlogUrl;

/**
 * Post list of the blog home and of a category page.
 *
 * Can also be created from code to show the latest posts elsewhere: set the
 * "page_size" data and a template of your own. Page title, meta data and
 * breadcrumbs are only set when the "apply_page_meta" argument is true (the blog pages).
 */
class PostList extends Template implements IdentityInterface
{
    public const PAGE_SIZE = Config::DEFAULT_POSTS_PER_PAGE;
    public const MOBILE_BREAKPOINT_PX = 768;
    public const MEDIA_DIR = 'slova/blog';

    /** @var Collection|null */
    private ?Collection $collection = null;

    /** @var Category[]|null */
    private ?array $categoriesById = null;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $collectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param Registry $registry
     * @param BlogUrl $blogUrl
     * @param Resizer $resizer
     * @param Config $config
     * @param DateFormatter $dateFormatter
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        private readonly Resizer $resizer,
        private readonly Config $config,
        private readonly DateFormatter $dateFormatter,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Published posts of the current page (and category, on a category page).
     *
     * @return Collection
     */
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

            $collection->setPageSize($this->getPageSize())->setCurPage($this->getRequestedPage());
            $this->collection = $collection;
        }
        return $this->collection;
    }

    /**
     * Posts per page: the "page_size" data, else the setting.
     *
     * @return int
     */
    public function getPageSize(): int
    {
        $size = (int)$this->getData('page_size');
        return $size > 0 ? $size : $this->config->getPostsPerPage();
    }

    /**
     * Current category on a category page.
     *
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        $category = $this->registry->registry('current_blog_category');
        return $category instanceof Category ? $category : null;
    }

    /**
     * Blog title (setting), for headings.
     *
     * @return string
     */
    public function getBlogTitle(): string
    {
        return $this->config->getTitle();
    }

    /**
     * Heading of the page: category name or blog title.
     *
     * @return string
     */
    public function getHeading(): string
    {
        return $this->getCategory() ? (string)$this->getCategory()->getName() : $this->getBlogTitle();
    }

    /**
     * Whether the title band already shows the H1.
     *
     * @return bool
     */
    public function isTitleBandEnabled(): bool
    {
        return $this->config->isTitleBandEnabled();
    }

    /**
     * Absolute URL of a post.
     *
     * @param string $urlKey
     * @return string
     */
    public function getPostUrl(string $urlKey): string
    {
        return $this->blogUrl->getPostUrl($urlKey);
    }

    /**
     * Absolute URL of a category page.
     *
     * @param string $urlKey
     * @return string
     */
    public function getCategoryUrl(string $urlKey): string
    {
        return $this->blogUrl->getCategoryUrl($urlKey);
    }

    /**
     * Active categories of a post.
     *
     * @param Post $post
     * @return Category[]
     */
    public function getCategoriesForPost(Post $post): array
    {
        $ids = $post->getCategoryIds();
        if (!$ids) {
            return [];
        }
        $this->loadCategoriesById();
        return array_values(array_intersect_key($this->categoriesById, array_flip(array_map('intval', $ids))));
    }

    /**
     * Media path of the featured image.
     *
     * @param Post $post
     * @return string
     */
    public function getFeaturedImagePath(Post $post): string
    {
        return self::resolveImagePath((string)$post->getFeaturedImage());
    }

    /**
     * Media path of the mobile thumbnail.
     *
     * @param Post $post
     * @return string
     */
    public function getThumbnailMobilePath(Post $post): string
    {
        return self::resolveImagePath((string)$post->getThumbnailMobile());
    }

    /**
     * URL of a resized copy of a media image.
     *
     * @param string $path
     * @param int $width
     * @param string $format
     * @return string
     */
    public function processImage(string $path, int $width, string $format = 'webp'): string
    {
        return $this->resizer->process($path, $width, $format);
    }

    /**
     * Whether resized WebP copies are served.
     *
     * @return bool
     */
    public function isResponsiveImagesEnabled(): bool
    {
        return $this->resizer->isEnabled();
    }

    /**
     * Publish date in the store language.
     *
     * @param Post $post
     * @return string
     */
    public function getFormattedDate(Post $post): string
    {
        return $this->dateFormatter->format((string)($post->getPublishedAt() ?: $post->getCreatedAt()));
    }

    /**
     * Pager for screens wider than a phone.
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        /** @var Pager $pager */
        $pager = $this->getLayout()->createBlock(Pager::class, 'slova.blog.post.pager');
        $pager->setLimit($this->getPageSize())
            ->setShowAmounts(false)
            ->setShowPerPage(false)
            ->setPageVarName('p')
            ->setCollection($this->getPosts());
        return $pager->toHtml();
    }

    /**
     * Current page number.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return (int)$this->getPosts()->getCurPage();
    }

    /**
     * Last page number.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return (int)$this->getPosts()->getLastPageNumber();
    }

    /**
     * @inheritDoc
     */
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

    /**
     * Media path for a stored image value (a bare file name lives in slova/blog).
     *
     * @param string $value
     * @return string
     */
    public static function resolveImagePath(string $value): string
    {
        if ($value === '') {
            return '';
        }
        return str_contains($value, '/') ? ltrim($value, '/') : self::MEDIA_DIR . '/' . $value;
    }

    /**
     * Page title, meta data, canonical URL and breadcrumbs of the blog pages.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getData('apply_page_meta')) {
            return $this;
        }

        $category = $this->getCategory();
        $page = $this->getRequestedPage();

        if ($category) {
            $this->pageConfig->getTitle()->set((string)$category->getName());
            $canonical = $this->blogUrl->getCategoryUrl((string)$category->getUrlKey());
        } else {
            $this->pageConfig->getTitle()->set($this->config->getMetaTitle());
            $description = $this->config->getMetaDescription();
            if ($description !== '') {
                $this->pageConfig->setDescription($description);
            }
            $canonical = $this->blogUrl->getBaseUrl();
        }
        if ($page > 1) {
            $canonical .= '?p=' . $page;
        }
        $this->pageConfig->addRemotePageAsset($canonical, 'canonical', ['attributes' => ['rel' => 'canonical']]);

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->_storeManager->getStore()->getBaseUrl(),
            ]);
            $blogCrumb = ['label' => $this->getBlogTitle(), 'title' => $this->getBlogTitle()];
            if ($category) {
                $blogCrumb['link'] = $this->blogUrl->getBaseUrl();
            }
            $breadcrumbs->addCrumb('blog', $blogCrumb);
            if ($category) {
                $breadcrumbs->addCrumb('blog_category', [
                    'label' => $category->getName(),
                    'title' => $category->getName(),
                ]);
            }
        }

        return $this;
    }

    /**
     * Page number from the "p" query parameter.
     *
     * @return int
     */
    private function getRequestedPage(): int
    {
        return max(1, (int)$this->getRequest()->getParam('p', 1));
    }

    /**
     * Load active categories once, keyed by id.
     *
     * @return void
     */
    private function loadCategoriesById(): void
    {
        if ($this->categoriesById !== null) {
            return;
        }
        $this->categoriesById = [];
        foreach ($this->categoryCollectionFactory->create()->addActiveFilter() as $category) {
            $this->categoriesById[(int)$category->getId()] = $category;
        }
    }
}
