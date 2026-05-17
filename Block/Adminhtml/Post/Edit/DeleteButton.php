<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Post\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $postId = $this->getPostId();
        if (!$postId) {
            return [];
        }
        return [
            'label'      => __('Șterge articol'),
            'class'      => 'delete',
            'on_click'   => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Sigur ștergi acest articol?'),
                $this->getUrl('slova_blog/post/delete', ['post_id' => $postId])
            ),
            'sort_order' => 20,
        ];
    }
}
