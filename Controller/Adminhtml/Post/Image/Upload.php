<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post\Image;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\Controller\ResultFactory;
use Slova\Blog\Controller\Adminhtml\Post;

class Upload extends Post
{
    public function __construct(
        Context $context,
        private readonly ImageUploader $imageUploader
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $fieldName = 'featured_image';
            if (!empty($this->getRequest()->getFiles('og_image'))) {
                $fieldName = 'og_image';
            }
            $result = $this->imageUploader->saveFileToTmpDir($fieldName);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
