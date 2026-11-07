<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Controller\Post;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\Preview\Storage;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;

/**
 * /blog/post/preview/token/…: a post as it was in the admin form when Preview was pressed,
 * active or not, published or not. The page is not cached and not indexed; the link
 * stops working after an hour.
 */
class Preview implements HttpGetActionInterface
{
    public const REGISTRY_FLAG = 'slova_blog_preview';

    /**
     * @param RequestInterface $request
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Storage $storage
     * @param PostFactory $postFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param Registry $registry
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $resultPageFactory,
        private readonly ForwardFactory $resultForwardFactory,
        private readonly Storage $storage,
        private readonly PostFactory $postFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly Registry $registry
    ) {
    }

    /**
     * Render the preview, or 404 when the link is unknown or expired.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $data = $this->storage->load((string)$this->request->getParam('token'));
        if ($data === null) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $post = $this->postFactory->create();
        $post->setData($data);
        $this->registry->register('current_blog_post', $post);
        $this->registry->register(self::REGISTRY_FLAG, true);

        $categoryIds = (array)$post->getData('category_ids');
        if ($categoryIds) {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, (int)reset($categoryIds));
            if ($category->getId()) {
                $this->registry->register('current_blog_category', $category);
            }
        }

        $page = $this->resultPageFactory->create();
        $page->getConfig()->setRobots('NOINDEX,NOFOLLOW');
        return $page;
    }
}
