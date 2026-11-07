<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block\Sidebar;

use Magento\Cms\Block\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;
use Slova\Blog\Model\Config;

/**
 * The CMS block chosen in Stores > Configuration > Slova > Blog > Display, in the sidebar.
 * Renders nothing when none is chosen. Page cache tags come from the CMS block itself.
 */
class CmsBlock extends Block
{
    /**
     * @param Context $context
     * @param FilterProvider $filterProvider
     * @param StoreManagerInterface $storeManager
     * @param BlockFactory $blockFactory
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        BlockFactory $blockFactory,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $filterProvider, $storeManager, $blockFactory, $data);
    }

    /**
     * Identifier from the setting.
     *
     * @return string
     */
    public function getBlockId()
    {
        return $this->config->getSidebarCmsBlock();
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        if ($this->getBlockId() === '') {
            return '';
        }
        $html = (string)parent::_toHtml();
        return trim($html) !== '' ? '<div class="slova-blog-sidebar-banner">' . $html . '</div>' : '';
    }
}
