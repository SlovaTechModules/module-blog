<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Controller\Adminhtml\Category;

class Index extends Category
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Slova_Blog::category');
        $resultPage->getConfig()->getTitle()->prepend(__('Categorii Jurnal'));
        return $resultPage;
    }
}
