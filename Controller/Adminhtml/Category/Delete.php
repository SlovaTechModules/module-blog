<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Slova\Blog\Controller\Adminhtml\Category;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;

class Delete extends Category
{
    public function __construct(
        Context $context,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('category_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid category.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->categoryFactory->create();
            $this->categoryResource->load($model, $id);
            if (!$model->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('This category no longer exists.'));
            }
            $this->categoryResource->delete($model);
            $this->messageManager->addSuccessMessage(__('The category has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
