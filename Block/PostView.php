<?php
declare(strict_types=1);

namespace Slova\Blog\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Model\Category;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Slova\Blog\Model\Url as BlogUrl;
use Slova\Blog\Service\ContentProcessor;

class PostView extends Template implements IdentityInterface
{
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ContentProcessor $contentProcessor,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProcessedContent(): string
    {
        $post = $this->getPost();
        if (!$post) {
            return '';
        }
        return $this->contentProcessor->process((string)$post->getContent());
    }

    public function getPost(): ?Post
    {
        return $this->registry->registry('current_blog_post');
    }

    public function getFeaturedImagePath(): string
    {
        return $this->resolveImagePath((string)$this->getPost()?->getFeaturedImage());
    }

    private function resolveImagePath(string $value): string
    {
        if ($value === '') {
            return '';
        }
        return str_contains($value, '/') ? ltrim($value, '/') : 'slova/blog/' . $value;
    }

    public function shouldShowFeaturedImage(): bool
    {
        $post = $this->getPost();
        if (!$post) {
            return false;
        }
        return (int)$post->getShowFeaturedImage() === 1 && $this->getFeaturedImagePath() !== '';
    }

    public function getSideImagePath(): string
    {
        return $this->resolveImagePath((string)$this->getPost()?->getSideImage());
    }

    public function getSideText(): string
    {
        $post = $this->getPost();
        return $post ? trim((string)$post->getSideText()) : '';
    }

    public function getSideImageAlt(): string
    {
        $post = $this->getPost();
        return $post ? (string)$post->getSideImageAlt() : '';
    }

    public function hasSideSection(): bool
    {
        return $this->getSideText() !== '' || $this->getSideImagePath() !== '';
    }

    public function getSanitizedSideText(): string
    {
        $raw = $this->getSideText();
        if ($raw === '') {
            return '';
        }
        $escaped = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
        $allowedTags = [
            '&lt;strong&gt;'  => '<strong>',
            '&lt;/strong&gt;' => '</strong>',
            '&lt;em&gt;'      => '<em>',
            '&lt;/em&gt;'     => '</em>',
        ];
        $withTags = strtr($escaped, $allowedTags);

        $paragraphs = preg_split('/\n\s*\n/', $withTags) ?: [];
        $rendered = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p !== '') {
                $rendered[] = '<p>' . nl2br($p) . '</p>';
            }
        }
        return implode('', $rendered);
    }

    public function getOgImageUrl(): string
    {
        $post = $this->getPost();
        if (!$post) {
            return '';
        }
        $path = $this->resolveImagePath((string)($post->getOgImage() ?: $post->getFeaturedImage()));
        if ($path === '') {
            return '';
        }
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    }

    public function getCanonicalUrl(): string
    {
        $post = $this->getPost();
        return $post ? $this->blogUrl->getPostUrl((string)$post->getUrlKey()) : '';
    }

    public function getCategoryUrl(string $urlKey): string
    {
        return $this->blogUrl->getCategoryUrl($urlKey);
    }

    public function getFormattedDate(): string
    {
        $post = $this->getPost();
        if (!$post) {
            return '';
        }
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

    public function getIsoDate(): string
    {
        $post = $this->getPost();
        if (!$post) {
            return '';
        }
        $date = $post->getPublishedAt() ?: $post->getCreatedAt();
        return $date ? date('c', strtotime($date)) : '';
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        $post = $this->getPost();
        if (!$post) {
            return [];
        }
        $ids = $post->getCategoryIds();
        if (!$ids) {
            return [];
        }
        $collection = $this->categoryCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('category_id', ['in' => $ids]);
        return array_values(iterator_to_array($collection->getIterator()));
    }

    /**
     * @return Product[]
     */
    public function getRelatedProducts(): array
    {
        $post = $this->getPost();
        if (!$post) {
            return [];
        }
        $ids = $post->getProductIds();
        if (!$ids) {
            return [];
        }
        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($ids)
            ->addAttributeToSelect(['name', 'price', 'special_price', 'thumbnail', 'small_image', 'url_key', 'image'])
            ->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [
                ProductVisibility::VISIBILITY_IN_CATALOG,
                ProductVisibility::VISIBILITY_BOTH,
            ]])
            ->addUrlRewrite()
            ->addFinalPrice();
        return array_values($collection->getItems());
    }

    public function getIdentities(): array
    {
        $post = $this->getPost();
        if (!$post) {
            return [PostInterface::CACHE_TAG];
        }
        return [PostInterface::CACHE_TAG . '_' . $post->getId()];
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $post = $this->getPost();
        if (!$post) {
            return $this;
        }

        $title = (string)($post->getMetaTitle() ?: $post->getTitle());
        $this->pageConfig->getTitle()->set($title);

        $description = (string)($post->getMetaDescription() ?: $post->getShortContent());
        if ($description !== '') {
            $this->pageConfig->setDescription(strip_tags($description));
        }

        $robots = (string)($post->getMetaRobots() ?: 'INDEX,FOLLOW');
        $this->pageConfig->setRobots($robots);

        $this->addSocialMetadata($post, $title, $description);

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Acasă'),
                'title' => __('Acasă'),
                'link'  => $this->_storeManager->getStore()->getBaseUrl(),
            ]);
            $breadcrumbs->addCrumb('jurnal', [
                'label' => __('Jurnal de bord'),
                'title' => __('Jurnal de bord'),
                'link'  => $this->blogUrl->getBaseUrl(),
            ]);
            $currentCategory = $this->registry->registry('current_blog_category');
            if ($currentCategory && $currentCategory->getId()) {
                $breadcrumbs->addCrumb('blog_category', [
                    'label' => $currentCategory->getName(),
                    'title' => $currentCategory->getName(),
                    'link'  => $this->blogUrl->getCategoryUrl((string)$currentCategory->getUrlKey()),
                ]);
            }
            $breadcrumbs->addCrumb('post', [
                'label' => $post->getTitle(),
                'title' => $post->getTitle(),
            ]);
        }

        return $this;
    }

    private function addSocialMetadata(Post $post, string $title, string $description): void
    {
        $url = $this->getCanonicalUrl();
        $image = $this->getOgImageUrl();
        $cleanDesc = trim(strip_tags($description));

        $this->pageConfig->setMetadata('og:type', 'article');
        $this->pageConfig->setMetadata('og:title', $title);
        if ($cleanDesc !== '') {
            $this->pageConfig->setMetadata('og:description', $cleanDesc);
        }
        if ($url !== '') {
            $this->pageConfig->setMetadata('og:url', $url);
        }
        if ($image !== '') {
            $this->pageConfig->setMetadata('og:image', $image);
        }
        $this->pageConfig->setMetadata('og:site_name', (string)$this->_storeManager->getStore()->getName());

        $publishedIso = $this->getIsoDate();
        if ($publishedIso !== '') {
            $this->pageConfig->setMetadata('article:published_time', $publishedIso);
        }
        if ($post->getUpdatedAt()) {
            $this->pageConfig->setMetadata('article:modified_time', date('c', strtotime((string)$post->getUpdatedAt())));
        }

        $this->pageConfig->setMetadata('twitter:card', $image !== '' ? 'summary_large_image' : 'summary');
        $this->pageConfig->setMetadata('twitter:title', $title);
        if ($cleanDesc !== '') {
            $this->pageConfig->setMetadata('twitter:description', $cleanDesc);
        }
        if ($image !== '') {
            $this->pageConfig->setMetadata('twitter:image', $image);
        }
    }
}
