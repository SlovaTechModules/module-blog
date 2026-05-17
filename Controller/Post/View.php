<?php
declare(strict_types=1);

namespace Slova\Blog\Controller\Post;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Model\CategoryFactory;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\ResourceModel\Category as CategoryResource;
use Slova\Blog\Model\ResourceModel\Post as PostResource;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ForwardFactory $resultForwardFactory,
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryResource $categoryResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        if (!$id) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $post = $this->postFactory->create();
        $this->postResource->load($post, $id);

        if (!$post->getId() || (int)$post->getIsActive() !== 1) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $publishedAt = $post->getPublishedAt();
        if ($publishedAt && strtotime($publishedAt) > time()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $this->registry->register('current_blog_post', $post);

        $categoryIds = $post->getCategoryIds();
        $primaryCategoryId = !empty($categoryIds) ? (int)$categoryIds[0] : 0;
        if ($primaryCategoryId) {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $primaryCategoryId);
            if ($category->getId() && (int)$category->getIsActive() === 1) {
                $this->registry->register('current_blog_category', $category);
            }
        }

        return $this->resultPageFactory->create();
    }
}
