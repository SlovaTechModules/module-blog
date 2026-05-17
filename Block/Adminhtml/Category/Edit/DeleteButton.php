<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $id = $this->getCategoryId();
        if (!$id) {
            return [];
        }
        return [
            'label'      => __('Șterge categoria'),
            'class'      => 'delete',
            'on_click'   => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Sigur ștergi această categorie?'),
                $this->getUrl('slova_blog/category/delete', ['category_id' => $id])
            ),
            'sort_order' => 20,
        ];
    }
}
