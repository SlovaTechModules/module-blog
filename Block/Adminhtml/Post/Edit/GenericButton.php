<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Post\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

abstract class GenericButton
{
    public function __construct(
        protected readonly Context $context,
        protected readonly Registry $registry
    ) {
    }

    public function getPostId(): ?int
    {
        $post = $this->registry->registry('slova_blog_post');
        return $post && $post->getId() ? (int)$post->getId() : null;
    }

    protected function getUrl(string $route, array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
