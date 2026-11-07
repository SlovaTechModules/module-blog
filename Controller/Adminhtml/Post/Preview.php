<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Slova\Blog\Block\PostList;
use Slova\Blog\Controller\Adminhtml\Post;
use Slova\Blog\Model\Post\FormData;
use Slova\Blog\Model\PostFactory;
use Slova\Blog\Model\Preview\Storage;
use Slova\Blog\Model\ResourceModel\Post as PostResource;

/**
 * Preview button (AJAX): takes what is in the form right now and returns a storefront
 * link that shows it.
 *
 * A draft (a new post, or one that is not active) is also saved first, as a draft that
 * stays inactive, so the work is not lost; the form gets the post id back. A live post is
 * never saved from here: visitors keep seeing the saved version until Save is pressed.
 */
class Preview extends Post implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param FormData $formData
     * @param Storage $storage
     * @param PostFactory $postFactory
     * @param PostResource $postResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly FormData $formData,
        private readonly Storage $storage,
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): JsonResult
    {
        /** @var JsonResult $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $form = json_decode((string)$this->getRequest()->getParam('post'), true);
        if (!is_array($form)) {
            return $result->setData(['error' => (string)__('The preview could not be created.')]);
        }

        try {
            $data = $this->formData->normalize($form)['data'];
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage()]);
        }

        $saved = false;
        $postId = (int)($data['post_id'] ?? 0);
        if (trim((string)($data['title'] ?? '')) !== '' && $this->isDraft($postId)) {
            try {
                $postId = $this->saveDraft($postId, $data);
                $data['post_id'] = $postId;
                $saved = true;
            } catch (\Exception $e) {
                // The preview still opens; the draft can be saved with Save Draft.
                $this->logger->warning('Slova_Blog: draft not saved on preview: ' . $e->getMessage());
            }
        }

        $data['created_at'] ??= date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $token = $this->storage->save($data);

        return $result->setData([
            'url' => $this->storage->getUrl($token),
            'post_id' => $postId ?: null,
            'saved' => $saved,
            'images' => $this->getImageValues($data),
        ]);
    }

    /**
     * New posts and posts that are not active are drafts.
     *
     * @param int $postId
     * @return bool
     */
    private function isDraft(int $postId): bool
    {
        if (!$postId) {
            return true;
        }
        $post = $this->postFactory->create();
        $this->postResource->load($post, $postId);
        return !$post->getId() || (int)$post->getIsActive() !== 1;
    }

    /**
     * Save the form as an inactive post; returns its id.
     *
     * @param int $postId
     * @param array $data
     * @return int
     * @throws \Exception
     */
    private function saveDraft(int $postId, array $data): int
    {
        $post = $this->postFactory->create();
        if ($postId) {
            $this->postResource->load($post, $postId);
        }
        $data['is_active'] = 0;
        if (!$post->getId()) {
            unset($data['post_id']);
        }
        $post->setData($data);
        $this->postResource->save($post);
        return (int)$post->getId();
    }

    /**
     * Image fields as the uploader expects them, so the form keeps pointing at the
     * moved files.
     *
     * @param array $data
     * @return array<string,array>
     */
    private function getImageValues(array $data): array
    {
        $mediaUrl = $this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
        $values = [];
        foreach (FormData::IMAGE_FIELDS as $field) {
            $name = (string)($data[$field] ?? '');
            $values[$field] = $name === ''
                ? []
                : [['name' => $name, 'url' => $mediaUrl . PostList::resolveImagePath($name)]];
        }
        return $values;
    }
}
