<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentModifiers;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;

class ComponentModifierContext
{
    public function __construct(
        private readonly Component $component,
        private readonly MagewireArguments $arguments
    ) {
    }

    public function component(): Component
    {
        return $this->component;
    }

    public function arguments(): MagewireArguments
    {
        return $this->arguments;
    }
}
