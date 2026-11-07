<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Controller\Category;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\Url as BlogUrl;

/**
 * Posts of one category.
 */
class View implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param RedirectFactory $redirectFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param Registry $registry
     * @param BlogUrl $blogUrl
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $resultPageFactory,
        private readonly ForwardFactory $resultForwardFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl
    ) {
    }

    /**
     * Render the category, 404 if it is missing or disabled.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $id = (int)$this->request->getParam('id');
        $category = $this->categoryFactory->create();
        if ($id) {
            $this->categoryResource->load($category, $id);
        }
        if (!$category->getId() || (int)$category->getIsActive() !== 1) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $urlKey = (string)$category->getUrlKey();
        if (!$this->blogUrl->isPublicRequest($this->request, $this->blogUrl->getCategoryRequestPath($urlKey))) {
            $url = $this->blogUrl->getCategoryUrl($urlKey);
            $page = (int)$this->request->getParam('p');
            return $this->redirectFactory->create()
                ->setHttpResponseCode(301)
                ->setUrl($page > 1 ? $url . '?p=' . $page : $url);
        }

        $this->registry->register('current_blog_category', $category);
        return $this->resultPageFactory->create();
    }
}
