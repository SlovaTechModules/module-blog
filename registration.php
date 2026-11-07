<?php
/**
 * Slova_Blog — a blog for Magento 2 on Hyvä.
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Slova_Blog',
    __DIR__
);
