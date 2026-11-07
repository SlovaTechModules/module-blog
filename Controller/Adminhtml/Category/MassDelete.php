<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Slova\Blog\Controller\Adminhtml\Category;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\ResourceModel\Category\CollectionFactory;

class MassDelete extends Category
{
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryResource $categoryResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection as $item) {
            $this->categoryResource->delete($item);
            $count++;
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 category(ies) have been deleted.', $count));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
