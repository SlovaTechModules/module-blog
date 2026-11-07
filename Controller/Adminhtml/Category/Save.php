<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Slova\Blog\Controller\Adminhtml\Category;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\UrlKeyGenerator;

/**
 * Save a category from the admin form.
 */
class Save extends Category implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param UrlKeyGenerator $urlKeyGenerator
     */
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly UrlKeyGenerator $urlKeyGenerator
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

        $id = (int)$this->getRequest()->getParam('category_id');
        $model = $this->categoryFactory->create();
        if ($id) {
            $this->categoryResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This category no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        $source = !empty($data['url_key']) ? (string)$data['url_key'] : (string)($data['name'] ?? '');
        $data['url_key'] = $this->urlKeyGenerator->generate($source);

        if (empty($data['category_id'])) {
            unset($data['category_id']);
        }

        $model->setData($data);

        try {
            $this->categoryResource->save($model);
            $this->messageManager->addSuccessMessage(__('The category has been saved.'));
            $this->dataPersistor->clear('slova_blog_category');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['category_id' => (int)$model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving: %1', $e->getMessage()));
        }

        $this->dataPersistor->set('slova_blog_category', $data);
        return $resultRedirect->setPath('*/*/edit', ['category_id' => $id]);
    }
}
