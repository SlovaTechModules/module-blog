<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label'      => __('Înapoi'),
            'on_click'   => sprintf("location.href = '%s';", $this->getUrl('slova_blog/category/index')),
            'class'      => 'back',
            'sort_order' => 10,
        ];
    }
}
