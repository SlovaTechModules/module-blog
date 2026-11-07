<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Preview;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url as FrontendUrl;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Keeps the post data of a preview for an hour under a random token, and builds the
 * storefront link that shows it. Nothing is written to the post itself.
 */
class Storage
{
    public const LIFETIME = 3600;
    public const CACHE_TAG = 'slova_blog_preview';
    private const KEY_PREFIX = 'slova_blog_preview_';

    /**
     * @param CacheInterface $cache
     * @param Json $json
     * @param Random $random
     * @param FrontendUrl $frontendUrl
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Json $json,
        private readonly Random $random,
        private readonly FrontendUrl $frontendUrl,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Store post data; returns the token.
     *
     * @param array $postData
     * @return string
     */
    public function save(array $postData): string
    {
        $token = $this->random->getRandomString(32, Random::CHARS_LOWERS . Random::CHARS_DIGITS);
        $this->cache->save($this->json->serialize($postData), self::KEY_PREFIX . $token, [self::CACHE_TAG], self::LIFETIME);
        return $token;
    }

    /**
     * Post data of a token, or null when unknown or expired.
     *
     * @param string $token
     * @return array|null
     */
    public function load(string $token): ?array
    {
        if (!preg_match('/^[a-z0-9]{32}$/', $token)) {
            return null;
        }
        $raw = $this->cache->load(self::KEY_PREFIX . $token);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $data = $this->json->unserialize($raw);
        return is_array($data) ? $data : null;
    }

    /**
     * Storefront URL of the preview, in the default store view.
     *
     * @param string $token
     * @return string
     */
    public function getUrl(string $token): string
    {
        $storeId = (int)$this->storeManager->getDefaultStoreView()?->getId();
        return $this->frontendUrl->setScope($storeId)->getUrl(
            'blog/post/preview',
            ['token' => $token, '_nosid' => true]
        );
    }
}
