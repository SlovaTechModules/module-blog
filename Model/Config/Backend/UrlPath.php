<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Slova\Blog\Model\UrlKeyGenerator;

/**
 * Normalizes a URL prefix setting ("News/Blog " → "news/blog") before it is saved.
 */
class UrlPath extends Value
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param UrlKeyGenerator $urlKeyGenerator
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly UrlKeyGenerator $urlKeyGenerator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Normalize the path; refuse a value that has no usable characters.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $raw = trim((string)$this->getValue());
        $path = $this->urlKeyGenerator->generatePath($raw);
        if ($raw !== '' && $path === '') {
            throw new LocalizedException(
                __('"%1" cannot be used in a URL. Use letters, digits and hyphens.', $raw)
            );
        }
        $this->setValue($path);
        return parent::beforeSave();
    }
}
