<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\App\Action\Context;
use Slova\Blog\Controller\Adminhtml\Post;

class NewAction extends Post
{
    public function __construct(
        Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var Forward $forward */
        $forward = $this->resultForwardFactory->create();
        return $forward->forward('edit');
    }
}
