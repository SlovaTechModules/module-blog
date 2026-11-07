<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Slova\Blog\Controller\Adminhtml\Post;
use Slova\Blog\Model\Post\FormData;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\ResourceModel\Post as PostResource;

/**
 * Save a post from the admin form; with the "draft" parameter (Save Draft) the post is
 * stored inactive and the editor stays open.
 */
class Save extends Post implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param PostFactory $postFactory
     * @param PostResource $postResource
     * @param FormData $formData
     */
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly FormData $formData
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('post_id');
        $model = $this->postFactory->create();
        if ($id) {
            $this->postResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This post no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        $isDraft = (bool)$this->getRequest()->getParam('draft');
        try {
            $normalized = $this->formData->normalize($data);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['post_id' => $id]);
        }
        $data = $normalized['data'];
        if ($isDraft) {
            $data['is_active'] = 0;
        }
        if ($normalized['missing_skus']) {
            $this->messageManager->addWarningMessage(
                __('These SKUs were not found and have been ignored: %1', implode(', ', $normalized['missing_skus']))
            );
        }

        $model->setData($data);

        try {
            $this->postResource->save($model);
            $this->messageManager->addSuccessMessage(
                $isDraft ? __('The draft has been saved.') : __('The post has been saved.')
            );
            $this->dataPersistor->clear('slova_blog_post');

            if ($isDraft || $this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['post_id' => (int)$model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving: %1', $e->getMessage()));
        }

        $this->dataPersistor->set('slova_blog_post', $data);
        return $resultRedirect->setPath('*/*/edit', ['post_id' => $id]);
    }
}
