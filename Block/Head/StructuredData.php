<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block\Head;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Block\PostList;
use Slova\Blog\Model\Category;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\DateFormatter;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\Url as BlogUrl;

/**
 * Canonical link and JSON-LD (Article + BreadcrumbList) of a post page.
 */
class StructuredData extends Template
{
    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param BlogUrl $blogUrl
     * @param Config $config
     * @param DateFormatter $dateFormatter
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        private readonly Config $config,
        private readonly DateFormatter $dateFormatter,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Public URL of the current post.
     *
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        $post = $this->getPost();
        return $post ? $this->blogUrl->getPostUrl((string)$post->getUrlKey()) : '';
    }

    /**
     * JSON-LD payload, or ''.
     *
     * @return string
     */
    public function getJsonLd(): string
    {
        $post = $this->getPost();
        if (!$post) {
            return '';
        }

        $store = $this->_storeManager->getStore();
        $storeUrl = (string)$store->getBaseUrl();
        $postUrl = $this->blogUrl->getPostUrl((string)$post->getUrlKey());

        $imagePath = PostList::resolveImagePath((string)($post->getOgImage() ?: $post->getFeaturedImage()));
        $description = trim(strip_tags((string)($post->getMetaDescription() ?: $post->getShortContent())));

        $article = array_filter([
            '@type' => 'Article',
            '@id' => $postUrl . '#article',
            'headline' => (string)$post->getTitle(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
            'url' => $postUrl,
            'inLanguage' => $this->dateFormatter->getLanguageTag(),
            'datePublished' => $this->dateFormatter->iso((string)($post->getPublishedAt() ?: $post->getCreatedAt())),
            'dateModified' => $this->dateFormatter->iso((string)$post->getUpdatedAt()),
            'image' => $imagePath !== '' ? $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $imagePath : '',
            'description' => $description,
            'publisher' => [
                '@type' => 'Organization',
                'name' => (string)$store->getFrontendName(),
                'url' => $storeUrl,
            ],
        ], static fn ($value) => $value !== '' && $value !== null);

        $crumbs = [
            ['name' => (string)__('Home'), 'item' => $storeUrl],
            ['name' => $this->config->getTitle(), 'item' => $this->blogUrl->getBaseUrl()],
        ];
        $category = $this->registry->registry('current_blog_category');
        if ($category instanceof Category && $category->getId()) {
            $crumbs[] = [
                'name' => (string)$category->getName(),
                'item' => $this->blogUrl->getCategoryUrl((string)$category->getUrlKey()),
            ];
        }
        $crumbs[] = ['name' => (string)$post->getTitle(), 'item' => $postUrl];

        $items = [];
        foreach ($crumbs as $i => $crumb) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1] + $crumb;
        }

        $json = json_encode(
            [
                '@context' => 'https://schema.org',
                '@graph' => [$article, ['@type' => 'BreadcrumbList', 'itemListElement' => $items]],
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
        );
        return $json !== false ? $json : '';
    }

    /**
     * Current post.
     *
     * @return Post|null
     */
    private function getPost(): ?Post
    {
        if ($this->registry->registry(\Slova\Blog\Controller\Post\Preview::REGISTRY_FLAG)) {
            return null; // A preview is not the public page: no canonical, no structured data.
        }
        $post = $this->registry->registry('current_blog_post');
        return $post instanceof Post ? $post : null;
    }
}
