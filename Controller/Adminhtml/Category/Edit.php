<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Controller\Adminhtml\Category;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;

class Edit extends Category
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('category_id');
        $model = $this->categoryFactory->create();

        if ($id) {
            $this->categoryResource->load($model, (int)$id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Categoria nu există.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $this->registry->register('slova_blog_category', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Slova_Blog::category');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Editează: %1', $model->getName()) : __('Categorie nouă')
        );
        return $resultPage;
    }
}
