<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments;

use InvalidArgumentException;
use Magewirephp\Magewire\Component;

class ArgumentModifierRunner
{
    public function run(Component $component, MagewireArguments $arguments): void
    {
        $modifiers = $arguments->get('argumentModifiers', []);

        if (! is_array($modifiers)) {
            throw new InvalidArgumentException('The magewire:argument-modifiers layout argument must be an array.');
        }

        foreach ($modifiers as $name => $modifier) {
            if (! $modifier instanceof ArgumentModifierInterface) {
                throw new InvalidArgumentException(sprintf('Argument modifier "%s" must implement %s.', $name, ArgumentModifierInterface::class));
            }

            $modifier->modify($component, $arguments);
        }
    }
}
