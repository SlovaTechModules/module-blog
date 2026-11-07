<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * The module's settings (Stores > Configuration > Slova > Blog), read per store view.
 */
class Config
{
    public const XML_TITLE = 'slova_blog/general/title';
    public const XML_URL_PREFIX = 'slova_blog/general/url_prefix';
    public const XML_CATEGORY_URL_PREFIX = 'slova_blog/general/category_url_prefix';
    public const XML_META_TITLE = 'slova_blog/general/meta_title';
    public const XML_META_DESCRIPTION = 'slova_blog/general/meta_description';
    public const XML_POSTS_PER_PAGE = 'slova_blog/list/posts_per_page';
    public const XML_TITLE_BAND = 'slova_blog/display/title_band';
    public const XML_ACCENT_COLOR = 'slova_blog/display/accent_color';
    public const XML_SIDEBAR_CMS_BLOCK = 'slova_blog/display/sidebar_cms_block';
    public const XML_RELATED_TITLE = 'slova_blog/display/related_title';
    public const XML_RESPONSIVE_IMAGES = 'slova_blog/images/responsive';

    public const DEFAULT_URL_PREFIX = 'blog';
    public const DEFAULT_CATEGORY_URL_PREFIX = 'category';
    public const DEFAULT_POSTS_PER_PAGE = 12;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Blog title; empty setting = "Blog" in the store language.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTitle(?int $storeId = null): string
    {
        $title = $this->getString(self::XML_TITLE, $storeId);
        return $title !== '' ? $title : (string)__('Blog');
    }

    /**
     * URL path of the blog home, e.g. "blog" or "news/blog".
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUrlPrefix(?int $storeId = null): string
    {
        $prefix = trim($this->getString(self::XML_URL_PREFIX, $storeId), '/');
        return $prefix !== '' ? $prefix : self::DEFAULT_URL_PREFIX;
    }

    /**
     * URL segment between the blog prefix and a category's URL key.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryUrlPrefix(?int $storeId = null): string
    {
        $prefix = trim($this->getString(self::XML_CATEGORY_URL_PREFIX, $storeId), '/');
        return $prefix !== '' ? $prefix : self::DEFAULT_CATEGORY_URL_PREFIX;
    }

    /**
     * Meta title of the blog home; falls back to the blog title.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMetaTitle(?int $storeId = null): string
    {
        $title = $this->getString(self::XML_META_TITLE, $storeId);
        return $title !== '' ? $title : $this->getTitle($storeId);
    }

    /**
     * Meta description of the blog home.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMetaDescription(?int $storeId = null): string
    {
        return $this->getString(self::XML_META_DESCRIPTION, $storeId);
    }

    /**
     * Posts per list page.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getPostsPerPage(?int $storeId = null): int
    {
        $value = (int)$this->getString(self::XML_POSTS_PER_PAGE, $storeId);
        return $value > 0 ? $value : self::DEFAULT_POSTS_PER_PAGE;
    }

    /**
     * Whether the full-width title band is shown.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isTitleBandEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_TITLE_BAND, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Accent color (#rgb or #rrggbb), or '' to follow the theme.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAccentColor(?int $storeId = null): string
    {
        $color = $this->getString(self::XML_ACCENT_COLOR, $storeId);
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color) ? $color : '';
    }

    /**
     * Identifier of the CMS block shown in the sidebar, or ''.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSidebarCmsBlock(?int $storeId = null): string
    {
        return $this->getString(self::XML_SIDEBAR_CMS_BLOCK, $storeId);
    }

    /**
     * Heading of the related products slider.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRelatedTitle(?int $storeId = null): string
    {
        $title = $this->getString(self::XML_RELATED_TITLE, $storeId);
        return $title !== '' ? $title : (string)__('Featured in this post');
    }

    /**
     * Whether images are resized and converted to WebP.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isResponsiveImagesEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_RESPONSIVE_IMAGES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Trimmed string value of a store-view setting.
     *
     * @param string $path
     * @param int|null $storeId
     * @return string
     */
    private function getString(string $path, ?int $storeId): string
    {
        return trim((string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
