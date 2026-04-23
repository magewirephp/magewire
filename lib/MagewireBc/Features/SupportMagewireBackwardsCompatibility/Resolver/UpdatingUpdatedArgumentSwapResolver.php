<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\Resolver;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\CallHookArgumentResolverInterface;

class UpdatingUpdatedArgumentSwapResolver implements CallHookArgumentResolverInterface
{
    public function supports(Component $component, string $method): bool
    {
        return method_exists($component, $method) && method_exists($this, $method);
    }

    public function resolve(Component $component, string $method, array $params): array
    {
        return $this->$method($component, $method, $params);
    }

    /**
     * Swap parameters when backwards compatible.
     */
    private function updated(Component $component, string $method, array $params): array
    {
        return [$method, [$params[1], $params[0]]];
    }
}
