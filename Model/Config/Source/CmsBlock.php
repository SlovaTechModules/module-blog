<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Config\Source;

use Magento\Cms\Model\Config\Source\Block as CmsBlockSource;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * CMS blocks by identifier, with "None" first.
 */
class CmsBlock implements OptionSourceInterface
{
    /**
     * @param CmsBlockSource $cmsBlockSource
     */
    public function __construct(
        private readonly CmsBlockSource $cmsBlockSource
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return array_merge(
            [['value' => '', 'label' => __('-- None --')]],
            $this->cmsBlockSource->toOptionArray()
        );
    }
}
