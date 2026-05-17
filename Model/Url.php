<?php
declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Store\Model\StoreManagerInterface;
use Slova\Blog\Api\Data\CategoryInterface;
use Slova\Blog\Api\Data\PostInterface;

class Url
{
    public const BASE_PATH = 'jurnal-de-bord';
    public const CATEGORY_PREFIX = 'categorie';

    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->storeManager->getStore()->getBaseUrl(), '/') . '/' . self::BASE_PATH;
    }

    public function getPostUrl(string $urlKey): string
    {
        return $this->getBaseUrl() . '/' . $urlKey;
    }

    public function getCategoryUrl(string $urlKey): string
    {
        return $this->getBaseUrl() . '/' . self::CATEGORY_PREFIX . '/' . $urlKey;
    }

    public function getPostRequestPath(string $urlKey): string
    {
        return self::BASE_PATH . '/' . $urlKey;
    }

    public function getCategoryRequestPath(string $urlKey): string
    {
        return self::BASE_PATH . '/' . self::CATEGORY_PREFIX . '/' . $urlKey;
    }

    public function getPostTargetPath(int $postId): string
    {
        return self::BASE_PATH . '/post/view/id/' . $postId;
    }

    public function getCategoryTargetPath(int $categoryId): string
    {
        return self::BASE_PATH . '/category/view/id/' . $categoryId;
    }

    public function getIndexTargetPath(): string
    {
        return self::BASE_PATH . '/index/index';
    }
}
