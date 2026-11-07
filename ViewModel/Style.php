<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Slova\Blog\Model\Config;

/**
 * Settings the head styles need.
 */
class Style implements ArgumentInterface
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Accent color from the settings, or '' to follow the theme.
     *
     * @return string
     */
    public function getAccentColor(): string
    {
        return $this->config->getAccentColor();
    }

    /**
     * Card image fit: "cover" or "contain".
     *
     * @return string
     */
    public function getCardImageFit(): string
    {
        return $this->config->getCardImageFit();
    }
}
