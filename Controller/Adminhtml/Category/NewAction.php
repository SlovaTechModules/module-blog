<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Slova\Blog\Controller\Adminhtml\Category;

class NewAction extends Category
{
    public function __construct(
        Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
