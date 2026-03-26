<?php
/**
 * Copyright © W. Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information..
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::LIBRARY,
    'Magewirephp_Magewire/lib',
    __DIR__
);
