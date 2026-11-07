<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Slova\Blog\Model\Config;
use Slova\Blog\Model\UrlRewriteGenerator;

/**
 * After the blog settings are saved: rebuild every blog URL when a URL prefix changed.
 */
class RegenerateUrlRewrites implements ObserverInterface
{
    private const URL_PATHS = [Config::XML_URL_PREFIX, Config::XML_CATEGORY_URL_PREFIX];

    /**
     * @param UrlRewriteGenerator $urlRewriteGenerator
     */
    public function __construct(
        private readonly UrlRewriteGenerator $urlRewriteGenerator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $changed = (array)$observer->getEvent()->getData('changed_paths');
        if (array_intersect(self::URL_PATHS, $changed)) {
            $this->urlRewriteGenerator->regenerateAll();
        }
    }
}
