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
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\ResourceModel\Post as PostResource;
use Slova\Blog\Model\Url as BlogUrl;

/**
 * A single post. Its first category becomes the current category (breadcrumbs,
 * sidebar highlight, previous/next links).
 */
class View implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param RedirectFactory $redirectFactory
     * @param PostFactory $postFactory
     * @param PostResource $postResource
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
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly Registry $registry,
        private readonly BlogUrl $blogUrl
    ) {
    }

    /**
     * Render the post; 404 if it is missing, disabled or scheduled for later.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $id = (int)$this->request->getParam('id');
        $post = $this->postFactory->create();
        if ($id) {
            $this->postResource->load($post, $id);
        }
        if (!$post->getId() || (int)$post->getIsActive() !== 1) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
        $publishedAt = (string)$post->getPublishedAt();
        if ($publishedAt !== '' && strtotime($publishedAt) > time()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $urlKey = (string)$post->getUrlKey();
        if (!$this->blogUrl->isPublicRequest($this->request, $this->blogUrl->getPostRequestPath($urlKey))) {
            return $this->redirectFactory->create()
                ->setHttpResponseCode(301)
                ->setUrl($this->blogUrl->getPostUrl($urlKey));
        }

        $this->registry->register('current_blog_post', $post);

        $categoryIds = $post->getCategoryIds();
        if ($categoryIds) {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, (int)reset($categoryIds));
            if ($category->getId() && (int)$category->getIsActive() === 1) {
                $this->registry->register('current_blog_category', $category);
            }
        }

        return $this->resultPageFactory->create();
    }
}
