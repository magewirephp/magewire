<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\Plugin;

use Magewirephp\Magewire\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\CallHookArgumentResolverInterface;

use function Magewirephp\Magewire\store;

class SupportLifecycleHooksPlugin
{
    /**
     * @param CallHookArgumentResolverInterface[] $resolvers
     */
    public function __construct(
        private readonly array $resolvers = []
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCallHook(SupportLifecycleHooks $subject, $name, $params = []): array
    {
        $component = $subject->component();

        // Continue if components isn't available or backwards compatible.
        if (! is_string($name) || ! $component || ! store($component)->get('magewire:bc') ?? false) {
            return [$name, $params];
        }

        foreach ($this->resolvers as $resolver) {
            if (! $resolver->supports($component, $name)) {
                continue;
            }

            [$name, $params] = $resolver->resolve($component, $name, $params);
            break;
        }

        return [$name, $params];
    }
}
