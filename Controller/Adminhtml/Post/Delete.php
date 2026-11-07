<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Slova\Blog\Controller\Adminhtml\Post;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\ResourceModel\Post as PostResource;

class Delete extends Post
{
    public function __construct(
        Context $context,
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('post_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid post.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->postFactory->create();
            $this->postResource->load($model, $id);
            if (!$model->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('This post no longer exists.'));
            }
            $this->postResource->delete($model);
            $this->messageManager->addSuccessMessage(__('The post has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
