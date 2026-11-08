<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Controller\Post\Preview as PreviewController;
use Slova\Blog\Model\Category;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\DateFormatter;
use Slova\Blog\Model\Image\Resizer;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Slova\Blog\Model\SideTextFormatter;
use Slova\Blog\Model\Url as BlogUrl;
use Slova\Blog\Service\ContentProcessor;

/**
 * A single post: header, intro section or hero image, content, previous/next links
 * within its category and the related products slider. Also sets the page meta data,
 * Open Graph / Twitter Card tags and breadcrumbs.
 */
class PostView extends Template implements IdentityInterface
{
    /** @var string|null */
    private ?string $filteredContent = null;

    /** @var string[]|null media paths of the content's images */
    private ?array $contentImagePaths = null;

    /** @var CatalogConfig */
    private CatalogConfig $catalogConfig;

    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param BlogUrl $blogUrl
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ContentProcessor $contentProcessor
     * @param SideTextFormatter $sideTextFormatter
     * @param DateFormatter $dateFormatter
     * @param Config $config
     * @param FilterProvider $filterProvider
     * @param Resizer $resizer
     * @param array $data
     * @param CatalogConfig|null $catalogConfig
     */
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ContentProcessor $contentProcessor,
        private readonly SideTextFormatter $sideTextFormatter,
        private readonly DateFormatter $dateFormatter,
        private readonly Config $config,
        private readonly FilterProvider $filterProvider,
        private readonly Resizer $resizer,
        array $data = [],
        ?CatalogConfig $catalogConfig = null
    ) {
        parent::__construct($context, $data);
        $this->catalogConfig = $catalogConfig
            ?? \Magento\Framework\App\ObjectManager::getInstance()->get(CatalogConfig::class);
    }

    /**
     * Current post.
     *
     * @return Post|null
     */
    public function getPost(): ?Post
    {
        $post = $this->registry->registry('current_blog_post');
        return $post instanceof Post ? $post : null;
    }

    /**
     * Content HTML ready for display: CMS directives such as {{media url="…"}} (images
     * inserted from the editor's media gallery) resolved, then images and videos prepared.
     *
     * @return string
     */
    public function getProcessedContent(): string
    {
        return $this->contentProcessor->process($this->getFilteredContent());
    }

    /**
     * Media path of the featured image.
     *
     * @return string
     */
    public function getFeaturedImagePath(): string
    {
        return PostList::resolveImagePath((string)$this->getPost()?->getFeaturedImage());
    }

    /**
     * Whether the featured image is shown as the hero.
     *
     * Never when the same picture is already on the page, as the intro image or inside
     * the content: an image is shown once per page (the card keeps using it).
     *
     * @return bool
     */
    public function shouldShowFeaturedImage(): bool
    {
        $post = $this->getPost();
        $path = $this->getFeaturedImagePath();
        if (!$post || (int)$post->getShowFeaturedImage() !== 1 || $path === '') {
            return false;
        }
        if ($this->shouldShowSideImage() && $this->resizer->isSameImage($path, $this->getSideImagePath())) {
            return false;
        }
        return !$this->isInContent($path);
    }

    /**
     * Whether the intro image is shown: not when the same picture is inside the content.
     *
     * @return bool
     */
    public function shouldShowSideImage(): bool
    {
        $path = $this->getSideImagePath();
        return $path !== '' && !$this->isInContent($path);
    }

    /**
     * Media path of the intro image.
     *
     * @return string
     */
    public function getSideImagePath(): string
    {
        return PostList::resolveImagePath((string)$this->getPost()?->getSideImage());
    }

    /**
     * Raw intro text.
     *
     * @return string
     */
    public function getSideText(): string
    {
        return trim((string)$this->getPost()?->getSideText());
    }

    /**
     * Alt text of the intro image.
     *
     * @return string
     */
    public function getSideImageAlt(): string
    {
        return (string)$this->getPost()?->getSideImageAlt();
    }

    /**
     * Whether the post has an intro section.
     *
     * @return bool
     */
    public function hasSideSection(): bool
    {
        return $this->getSideText() !== '' || $this->shouldShowSideImage();
    }

    /**
     * Intro text as safe HTML (only <strong> and <em> kept).
     *
     * @return string
     */
    public function getSanitizedSideText(): string
    {
        return $this->sideTextFormatter->format($this->getSideText());
    }

    /**
     * Absolute URL of the share image (Open Graph image, else featured image).
     *
     * @return string
     */
    public function getOgImageUrl(): string
    {
        $post = $this->getPost();
        if (!$post) {
            return '';
        }
        $path = PostList::resolveImagePath((string)($post->getOgImage() ?: $post->getFeaturedImage()));
        if ($path === '') {
            return '';
        }
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;
    }

    /**
     * Public URL of the post.
     *
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        $post = $this->getPost();
        return $post ? $this->blogUrl->getPostUrl((string)$post->getUrlKey()) : '';
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
     * Publish date in the store language.
     *
     * @return string
     */
    public function getFormattedDate(): string
    {
        return $this->dateFormatter->format($this->getEffectiveDate());
    }

    /**
     * Publish date in ISO 8601.
     *
     * @return string
     */
    public function getIsoDate(): string
    {
        return $this->dateFormatter->iso($this->getEffectiveDate());
    }

    /**
     * Heading of the related products slider (setting).
     *
     * @return string
     */
    public function getRelatedTitle(): string
    {
        return $this->config->getRelatedTitle();
    }

    /**
     * Active categories of the post.
     *
     * @return Category[]
     */
    public function getCategories(): array
    {
        $ids = $this->getPost()?->getCategoryIds();
        if (!$ids) {
            return [];
        }
        $collection = $this->categoryCollectionFactory->create()
            ->addActiveFilter()
            ->applyDefaultOrder()
            ->addFieldToFilter('category_id', ['in' => $ids]);
        return array_values($collection->getItems());
    }

    /**
     * Related products that are enabled, visible in the catalog and in this website,
     * in the order they were entered.
     *
     * @return Product[]
     */
    public function getRelatedProducts(): array
    {
        $ids = $this->getPost()?->getProductIds();
        if (!$ids) {
            return [];
        }
        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($ids)
            ->addStoreFilter()
            // Every "used in product listing" attribute, as on category pages, so the theme's
            // product card gets the data its labels and badges read (e.g. a custom gift flag).
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addAttributeToSelect(['name', 'price', 'special_price', 'special_from_date', 'special_to_date',
                'thumbnail', 'small_image', 'url_key', 'image'])
            ->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [
                ProductVisibility::VISIBILITY_IN_CATALOG,
                ProductVisibility::VISIBILITY_BOTH,
            ]])
            ->addUrlRewrite()
            ->addFinalPrice();

        $items = $collection->getItems();
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($items[(int)$id])) {
                $ordered[] = $items[(int)$id];
            }
        }
        return $ordered;
    }

    /**
     * Older post in the same category.
     *
     * @return array{post_id:int,url_key:string,title:string,url:string}|null
     */
    public function getPreviousPost(): ?array
    {
        return $this->getNeighborInCategory('prev');
    }

    /**
     * Newer post in the same category.
     *
     * @return array{post_id:int,url_key:string,title:string,url:string}|null
     */
    public function getNextPost(): ?array
    {
        return $this->getNeighborInCategory('next');
    }

    /**
     * @inheritDoc
     */
    public function getIdentities(): array
    {
        $post = $this->getPost();
        if (!$post) {
            return [PostInterface::CACHE_TAG];
        }
        return [PostInterface::CACHE_TAG . '_' . $post->getId()];
    }

    /**
     * Page title, meta data, social tags and breadcrumbs.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $post = $this->getPost();
        if (!$post) {
            return $this;
        }

        $isPreview = (bool)$this->registry->registry(PreviewController::REGISTRY_FLAG);
        $title = (string)($post->getMetaTitle() ?: $post->getTitle());
        $this->pageConfig->getTitle()->set($isPreview ? (string)__('Preview: %1', $title) : $title);

        $description = trim(strip_tags((string)($post->getMetaDescription() ?: $post->getShortContent())));
        if ($description !== '') {
            $this->pageConfig->setDescription($description);
        }
        $this->pageConfig->setRobots(
            $isPreview ? 'NOINDEX,NOFOLLOW' : (string)($post->getMetaRobots() ?: 'INDEX,FOLLOW')
        );

        $this->addSocialMetadata($post, $title, $description);

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->_storeManager->getStore()->getBaseUrl(),
            ]);
            $breadcrumbs->addCrumb('blog', [
                'label' => $this->config->getTitle(),
                'title' => $this->config->getTitle(),
                'link' => $this->blogUrl->getBaseUrl(),
            ]);
            $category = $this->registry->registry('current_blog_category');
            if ($category instanceof Category && $category->getId()) {
                $breadcrumbs->addCrumb('blog_category', [
                    'label' => $category->getName(),
                    'title' => $category->getName(),
                    'link' => $this->blogUrl->getCategoryUrl((string)$category->getUrlKey()),
                ]);
            }
            $breadcrumbs->addCrumb('post', [
                'label' => $post->getTitle(),
                'title' => $post->getTitle(),
            ]);
        }

        return $this;
    }

    /**
     * Post content with CMS directives resolved (computed once).
     *
     * @return string
     */
    private function getFilteredContent(): string
    {
        if ($this->filteredContent === null) {
            $content = (string)$this->getPost()?->getContent();
            $this->filteredContent = trim($content) === ''
                ? ''
                : (string)$this->filterProvider->getPageFilter()->filter($content);
        }
        return $this->filteredContent;
    }

    /**
     * Whether the picture at this media path is one of the content's images.
     *
     * @param string $path
     * @return bool
     */
    private function isInContent(string $path): bool
    {
        if ($this->contentImagePaths === null) {
            $this->contentImagePaths = $this->contentProcessor->getMediaImagePaths($this->getFilteredContent());
        }
        foreach ($this->contentImagePaths as $contentPath) {
            if ($this->resizer->isSameImage($path, $contentPath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Publish date, else creation date.
     *
     * @return string
     */
    private function getEffectiveDate(): string
    {
        $post = $this->getPost();
        return $post ? (string)($post->getPublishedAt() ?: $post->getCreatedAt()) : '';
    }

    /**
     * Neighbor post in the current (primary) category.
     *
     * @param string $direction prev|next
     * @return array{post_id:int,url_key:string,title:string,url:string}|null
     */
    private function getNeighborInCategory(string $direction): ?array
    {
        $post = $this->getPost();
        $category = $this->registry->registry('current_blog_category');
        if (!$post || !$category instanceof Category || !$category->getId()) {
            return null;
        }
        /** @var \Slova\Blog\Model\ResourceModel\Post $resource */
        $resource = $post->getResource();
        $row = $resource->getNeighborInCategory((int)$post->getId(), (int)$category->getId(), $direction);
        if (!$row) {
            return null;
        }
        $row['url'] = $this->blogUrl->getPostUrl($row['url_key']);
        return $row;
    }

    /**
     * Open Graph and Twitter Card tags.
     *
     * @param Post $post
     * @param string $title
     * @param string $description
     * @return void
     */
    private function addSocialMetadata(Post $post, string $title, string $description): void
    {
        $url = $this->getCanonicalUrl();
        $image = $this->getOgImageUrl();

        $this->pageConfig->setMetadata('og:type', 'article');
        $this->pageConfig->setMetadata('og:title', $title);
        if ($description !== '') {
            $this->pageConfig->setMetadata('og:description', $description);
        }
        if ($url !== '') {
            $this->pageConfig->setMetadata('og:url', $url);
        }
        if ($image !== '') {
            $this->pageConfig->setMetadata('og:image', $image);
        }
        $this->pageConfig->setMetadata('og:site_name', (string)$this->_storeManager->getStore()->getFrontendName());

        $published = $this->getIsoDate();
        if ($published !== '') {
            $this->pageConfig->setMetadata('article:published_time', $published);
        }
        $modified = $this->dateFormatter->iso((string)$post->getUpdatedAt());
        if ($modified !== '') {
            $this->pageConfig->setMetadata('article:modified_time', $modified);
        }

        $this->pageConfig->setMetadata('twitter:card', $image !== '' ? 'summary_large_image' : 'summary');
        $this->pageConfig->setMetadata('twitter:title', $title);
        if ($description !== '') {
            $this->pageConfig->setMetadata('twitter:description', $description);
        }
        if ($image !== '') {
            $this->pageConfig->setMetadata('twitter:image', $image);
        }
    }
}
