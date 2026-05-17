<?php
declare(strict_types=1);

namespace Slova\Blog\Block\Adminhtml\Post\Edit;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Slova\Blog\Model\Url as BlogUrl;

class PreviewLink extends Template
{
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getPostUrl(): ?string
    {
        $post = $this->registry->registry('slova_blog_post');
        if (!$post || !$post->getId() || !$post->getUrlKey()) {
            return null;
        }
        return $this->blogUrl->getPostUrl((string)$post->getUrlKey());
    }
}
