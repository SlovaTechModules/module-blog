<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * How card images fill their frame.
 */
class CardImageFit implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'cover', 'label' => __('Fill the frame (cropped, top kept)')],
            ['value' => 'contain', 'label' => __('Show the whole image')],
        ];
    }
}
