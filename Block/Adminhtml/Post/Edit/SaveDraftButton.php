<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Post\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * "Save Draft": saves the post inactive and stays in the editor. Shown for new posts and
 * posts that are not active; on a live post it would take the post off the site.
 */
class SaveDraftButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        $post = $this->registry->registry('slova_blog_post');
        if ($post && $post->getId() && (int)$post->getIsActive() === 1) {
            return [];
        }
        return [
            'label' => __('Save Draft'),
            'class' => 'save-draft',
            // Without it Magento adds onclick "location.href = <base URL>", which leaves the page.
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [[
                            'targetName' => 'slova_blog_post_form.slova_blog_post_form',
                            'actionName' => 'save',
                            'params' => [true, ['draft' => 1]],
                        ]],
                    ],
                ],
            ],
            'sort_order' => 70,
        ];
    }
}
