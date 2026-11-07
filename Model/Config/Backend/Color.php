<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Accepts an empty value or a hex color (#rgb / #rrggbb).
 */
class Color extends Value
{
    /**
     * Validate the color before it is saved.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());
        if ($value !== '' && !preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value)) {
            throw new LocalizedException(
                __('The accent color must be a hex color such as #0f766e, or empty.')
            );
        }
        $this->setValue($value);
        return parent::beforeSave();
    }
}
