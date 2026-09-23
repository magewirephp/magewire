<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentModifiers;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Changes a component during its build, before mount or hydration.
 */
interface ComponentModifierInterface extends ArgumentInterface
{
    public function modify(ComponentModifierContext $context): void;
}
