<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Slova\Blog\Model\Source\Categories;

/**
 * Blog categories for the Latest Posts widget, with "All categories" first.
 */
class WidgetCategory implements OptionSourceInterface
{
    /**
     * @param Categories $categories
     */
    public function __construct(
        private readonly Categories $categories
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return array_merge(
            [['value' => '', 'label' => __('All categories')]],
            $this->categories->toOptionArray()
        );
    }
}
