<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Slova\Blog\Controller\Adminhtml\Post;
use Slova\Blog\Model\ResourceModel\Post as PostResource;
use Slova\Blog\Model\ResourceModel\Post\CollectionFactory;

class MassDelete extends Post
{
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly PostResource $postResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection as $item) {
            $this->postResource->delete($item);
            $count++;
        }
        $this->messageManager->addSuccessMessage(__('%1 articole șterse.', $count));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
