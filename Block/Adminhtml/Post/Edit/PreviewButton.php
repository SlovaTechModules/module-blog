<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Post\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * "Preview": shows what is in the form right now in a new storefront tab
 * (view/adminhtml/web/js/post/preview.js). A draft is saved first; a live post is not.
 */
class PreviewButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Preview'),
            'class' => 'preview',
            // Without it Magento adds onclick "location.href = <base URL>", which leaves the page.
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'Slova_Blog/js/post/preview' => [
                        'url' => $this->getUrl('slova_blog/post/preview'),
                        'provider' => 'slova_blog_post_form.slova_blog_post_form_data_source',
                    ],
                ],
            ],
            'sort_order' => 40,
        ];
    }
}
