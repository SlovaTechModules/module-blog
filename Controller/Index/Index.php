<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Slova\Blog\Model\Url as BlogUrl;

/**
 * Blog home: the latest posts.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $redirectFactory
     * @param BlogUrl $blogUrl
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly BlogUrl $blogUrl
    ) {
    }

    /**
     * Render the list; any other way in (/blog with a custom prefix, /blog/index/index)
     * is sent to the public URL.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        if (!$this->blogUrl->isPublicRequest($this->request, $this->blogUrl->getIndexRequestPath())) {
            $url = $this->blogUrl->getBaseUrl();
            $page = (int)$this->request->getParam('p');
            return $this->redirectFactory->create()
                ->setHttpResponseCode(301)
                ->setUrl($page > 1 ? $url . '?p=' . $page : $url);
        }
        return $this->resultPageFactory->create();
    }
}
