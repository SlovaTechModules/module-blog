<?php
declare(strict_types=1);

namespace Slova\Blog\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class HeaderBand extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTitle(): string
    {
        $category = $this->registry->registry('current_blog_category');
        if ($category && $category->getName()) {
            return (string)$category->getName();
        }
        return (string)($this->getData('default_title') ?: __('Jurnal'));
    }
}
