<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Head;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Model\Post;
use Slova\Blog\Model\Url as BlogUrl;

class StructuredData extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCanonicalUrl(): string
    {
        $post = $this->registry->registry('current_blog_post');
        if (!$post instanceof Post) {
            return '';
        }
        return $this->blogUrl->getPostUrl((string)$post->getUrlKey());
    }

    public function getJsonLd(): string
    {
        $post = $this->registry->registry('current_blog_post');
        if (!$post instanceof Post) {
            return '';
        }

        $storeUrl = $this->_storeManager->getStore()->getBaseUrl();
        $storeName = (string)$this->_storeManager->getStore()->getName();
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $postUrl = $this->blogUrl->getPostUrl((string)$post->getUrlKey());

        $publishedAt = $post->getPublishedAt() ?: $post->getCreatedAt();
        $updatedAt = $post->getUpdatedAt();

        $image = (string)($post->getOgImage() ?: $post->getFeaturedImage());
        if ($image !== '') {
            $relativePath = str_contains($image, '/') ? ltrim($image, '/') : 'slova/blog/' . $image;
            $imageUrl = $mediaUrl . $relativePath;
        } else {
            $imageUrl = '';
        }
        $description = (string)($post->getMetaDescription() ?: $post->getShortContent());

        $article = [
            '@type' => 'Article',
            '@id' => $postUrl . '#article',
            'headline' => (string)$post->getTitle(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
            'url' => $postUrl,
            'inLanguage' => 'ro-RO',
            'datePublished' => $publishedAt ? date('c', strtotime((string)$publishedAt)) : null,
            'dateModified' => $updatedAt ? date('c', strtotime((string)$updatedAt)) : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => $storeName,
                'url' => $storeUrl,
            ],
        ];

        if ($imageUrl !== '') {
            $article['image'] = $imageUrl;
        }
        if (trim(strip_tags($description)) !== '') {
            $article['description'] = trim(strip_tags($description));
        }

        $crumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Acasă', 'item' => $storeUrl],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Jurnal de bord', 'item' => $this->blogUrl->getBaseUrl()],
        ];

        $currentCategory = $this->registry->registry('current_blog_category');
        $position = 3;
        if ($currentCategory && $currentCategory->getId()) {
            $crumbItems[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => (string)$currentCategory->getName(),
                'item'     => $this->blogUrl->getCategoryUrl((string)$currentCategory->getUrlKey()),
            ];
        }
        $crumbItems[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => (string)$post->getTitle(),
            'item'     => $postUrl,
        ];

        $breadcrumbs = [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbItems,
        ];

        $payload = [
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter($article, static fn ($v) => $v !== null && $v !== ''),
                $breadcrumbs,
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json !== false ? $json : '';
    }
}
