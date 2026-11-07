<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Slova\Blog\Model\Config;

/**
 * Full-width band under the header with the blog or category name.
 *
 * Renders an <h1> on list pages; on a post page (argument "heading" = false) the
 * post title is the page's only <h1>, so the band uses a <p>.
 */
class HeaderBand extends Template
{
    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Category name on a category page or post, else the blog title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        $category = $this->registry->registry('current_blog_category');
        if ($category && $category->getName()) {
            return (string)$category->getName();
        }
        return $this->config->getTitle();
    }

    /**
     * Whether the band text is the page heading.
     *
     * @return bool
     */
    public function isHeading(): bool
    {
        return $this->getData('heading') !== false;
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        return $this->config->isTitleBandEnabled() ? parent::_toHtml() : '';
    }
}
