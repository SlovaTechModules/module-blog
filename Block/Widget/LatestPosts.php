<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block\Widget;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Slova\Blog\Api\Data\PostInterface;
use Slova\Blog\Block\PostList;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\DateFormatter;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;
use Slova\Blog\Model\Url as BlogUrl;
use Slova\Blog\ViewModel\Image;

/**
 * Widget "Slova Blog: Latest Posts" (etc/widget.xml): the newest published posts, all or
 * from one category, as cards with a link to the blog. Insert it under Content > Widgets
 * or in a CMS page/block.
 */
class LatestPosts extends Template implements BlockInterface, IdentityInterface
{
    public const MAX_POSTS = 12;

    /** @var string */
    protected $_template = 'Slova_Blog::widget/latest-posts.phtml';

    /** @var Post[]|null */
    private ?array $posts = null;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $collectionFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param BlogUrl $blogUrl
     * @param Config $config
     * @param DateFormatter $dateFormatter
     * @param Image $image
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly BlogUrl $blogUrl,
        private readonly Config $config,
        private readonly DateFormatter $dateFormatter,
        private readonly Image $image,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * The newest published posts.
     *
     * @return Post[]
     */
    public function getPosts(): array
    {
        if ($this->posts === null) {
            $collection = $this->collectionFactory->create()
                ->addActiveFilter()
                ->addPublishedFilter()
                ->applyDefaultOrder();
            $categoryId = $this->getCategoryId();
            if ($categoryId) {
                $collection->addCategoryFilter($categoryId);
            }
            $collection->setPageSize($this->getPostsCount())->setCurPage(1);
            $this->posts = array_values($collection->getItems());
        }
        return $this->posts;
    }

    /**
     * Number of posts (1–12, default 3).
     *
     * @return int
     */
    public function getPostsCount(): int
    {
        $count = (int)$this->getData('posts_count');
        return $count > 0 ? min($count, self::MAX_POSTS) : 3;
    }

    /**
     * Heading, or ''.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return trim((string)$this->getData('title'));
    }

    /**
     * Whether the short description is shown.
     *
     * @return bool
     */
    public function isExcerptShown(): bool
    {
        return $this->getData('show_excerpt') === null || (bool)$this->getData('show_excerpt');
    }

    /**
     * URL of the "View all posts" link (the category page when one is chosen), or ''.
     *
     * @return string
     */
    public function getAllPostsUrl(): string
    {
        if ($this->getData('show_link') !== null && !(bool)$this->getData('show_link')) {
            return '';
        }
        $categoryId = $this->getCategoryId();
        if ($categoryId) {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $categoryId);
            if ($category->getId() && (int)$category->getIsActive() === 1) {
                return $this->blogUrl->getCategoryUrl((string)$category->getUrlKey());
            }
        }
        return $this->blogUrl->getBaseUrl();
    }

    /**
     * Absolute URL of a post.
     *
     * @param Post $post
     * @return string
     */
    public function getPostUrl(Post $post): string
    {
        return $this->blogUrl->getPostUrl((string)$post->getUrlKey());
    }

    /**
     * Card image: the featured image.
     *
     * @param Post $post
     * @return string
     */
    public function getImageHtml(Post $post): string
    {
        $path = PostList::resolveImagePath((string)$post->getFeaturedImage());
        if ($path === '') {
            return '<span class="slova-blog-widget__placeholder"></span>';
        }
        return $this->image->picture(
            $path,
            480,
            alt: (string)($post->getFeaturedImageAlt() ?: $post->getTitle()),
            imgClass: 'slova-blog-widget__img'
        );
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
     * ISO publish date.
     *
     * @param Post $post
     * @return string
     */
    public function getIsoDate(Post $post): string
    {
        return $this->dateFormatter->iso((string)($post->getPublishedAt() ?: $post->getCreatedAt()));
    }

    /**
     * Accent color from the settings, or ''.
     *
     * @return string
     */
    public function getAccentColor(): string
    {
        return $this->config->getAccentColor();
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
        return $tags;
    }

    /**
     * Category filter from the widget, or 0.
     *
     * @return int
     */
    private function getCategoryId(): int
    {
        return (int)$this->getData('category_id');
    }
}
