<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentModifiers;

use InvalidArgumentException;

class ComponentModifierRunner
{
    public function run(ComponentModifierContext $context): void
    {
        $modifiers = $context->getArguments()->get('modifiers', []);

        if (! is_array($modifiers)) {
            throw new InvalidArgumentException('The magewire:modifiers layout argument must be an array.');
        }

        foreach ($modifiers as $name => $modifier) {
            if (! $modifier instanceof ModifierInterface) {
                throw new InvalidArgumentException(sprintf('Component modifier "%s" must implement %s.', $name, ModifierInterface::class));
            }

            $modifier->modify($context);
        }
    }
}
