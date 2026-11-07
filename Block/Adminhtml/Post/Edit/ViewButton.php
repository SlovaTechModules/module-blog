<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Post\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Slova\Blog\Model\Url as BlogUrl;

/**
 * "View Post": opens the public page in a new tab. Only for a saved post that visitors
 * can see (active, publish date reached).
 */
class ViewButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param BlogUrl $blogUrl
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Registry $registry,
        private readonly BlogUrl $blogUrl,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $registry);
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        $post = $this->registry->registry('slova_blog_post');
        if (!$post || !$post->getId() || (int)$post->getIsActive() !== 1 || !$post->getUrlKey()) {
            return [];
        }
        $publishedAt = (string)$post->getPublishedAt();
        if ($publishedAt !== '' && strtotime($publishedAt) > time()) {
            return [];
        }

        $storeId = (int)$this->storeManager->getDefaultStoreView()?->getId();
        $url = $this->blogUrl->getPostUrl((string)$post->getUrlKey(), $storeId ?: null);
        return [
            'label' => __('View Post'),
            'class' => 'view',
            'on_click' => sprintf("window.open(%s, '_blank', 'noopener');", json_encode($url, JSON_UNESCAPED_SLASHES)),
            'sort_order' => 30,
        ];
    }
}
