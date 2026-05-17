<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Slova\Blog\Controller\Adminhtml\Category;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;

class Save extends Category
{
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource
    ) {
        parent::__construct($context);
    }

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
                $this->messageManager->addErrorMessage(__('Categoria nu există.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        if (empty($data['url_key']) && !empty($data['name'])) {
            $data['url_key'] = $this->slugify((string)$data['name']);
        } elseif (!empty($data['url_key'])) {
            $data['url_key'] = $this->slugify((string)$data['url_key']);
        }

        if (empty($data['category_id'])) {
            unset($data['category_id']);
        }

        $model->setData($data);

        try {
            $this->categoryResource->save($model);
            $this->messageManager->addSuccessMessage(__('Categoria a fost salvată.'));
            $this->dataPersistor->clear('slova_blog_category');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['category_id' => (int)$model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Eroare la salvare: %1', $e->getMessage()));
        }

        $this->dataPersistor->set('slova_blog_category', $data);
        return $resultRedirect->setPath('*/*/edit', ['category_id' => $id]);
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's',
            'ț' => 't', 'ţ' => 't', 'Ă' => 'a', 'Â' => 'a', 'Î' => 'i',
            'Ș' => 's', 'Ş' => 's', 'Ț' => 't', 'Ţ' => 't',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
        return trim($value, '-');
    }
}
