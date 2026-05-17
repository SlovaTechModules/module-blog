<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Category;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ForwardFactory $resultForwardFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        if (!$id) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $category = $this->categoryFactory->create();
        $this->categoryResource->load($category, $id);

        if (!$category->getId() || (int)$category->getIsActive() !== 1) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $this->registry->register('current_blog_category', $category);

        return $this->resultPageFactory->create();
    }
}
