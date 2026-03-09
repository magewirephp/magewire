<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver;

use Magewirephp\Magewire\Support\Factory;

class ComponentResolverFactory
{
    public function create(string $type, array $arguments = [])
    {
        return Factory::create($type, $arguments);
    }
}
