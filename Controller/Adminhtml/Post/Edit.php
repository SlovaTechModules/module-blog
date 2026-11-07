<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Controller\Adminhtml\Post;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\ResourceModel\Post as PostResource;

class Edit extends Post
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('post_id');
        $model = $this->postFactory->create();

        if ($id) {
            $this->postResource->load($model, (int)$id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This post no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $this->registry->register('slova_blog_post', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Slova_Blog::post');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit: %1', $model->getTitle()) : __('New Post')
        );
        return $resultPage;
    }
}
