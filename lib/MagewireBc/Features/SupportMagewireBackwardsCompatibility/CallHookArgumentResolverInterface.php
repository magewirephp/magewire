<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magewirephp\Magewire\Component;

interface CallHookArgumentResolverInterface
{
    public function supports(Component $component, string $method): bool;

    public function resolve(Component $component, string $method, array $params): array;
}
