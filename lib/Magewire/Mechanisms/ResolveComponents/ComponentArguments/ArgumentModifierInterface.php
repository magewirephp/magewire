<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Component;

/**
 * Changes resolved Magewire arguments before component mount or hydration.
 * The collection accepts any argument name, including future ones.
 */
interface ArgumentModifierInterface extends ArgumentInterface
{
    public function modify(Component $component, MagewireArguments $arguments): void;
}
