<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Category\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

abstract class GenericButton
{
    public function __construct(
        protected readonly Context $context,
        protected readonly Registry $registry
    ) {
    }

    public function getCategoryId(): ?int
    {
        $category = $this->registry->registry('slova_blog_category');
        return $category && $category->getId() ? (int)$category->getId() : null;
    }

    protected function getUrl(string $route, array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
