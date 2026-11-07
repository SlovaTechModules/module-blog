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
            'label'      => __('Delete Post'),
            'class'      => 'delete',
            'on_click'   => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this post?'),
                $this->getUrl('slova_blog/post/delete', ['post_id' => $postId])
            ),
            'sort_order' => 20,
        ];
    }
}
