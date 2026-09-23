<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Events;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\ArgumentModifierInterface;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;

class ConfigureArguments implements ArgumentModifierInterface
{
    public function modify(Component $component, MagewireArguments $arguments): void
    {
        if (! $component instanceof Basic || $component->scope !== 'resolved') {
            return;
        }

        $listeners = $arguments->get('listeners', []);
        $listeners['modifier:' . $component->scope] = 'onModifierAdded';
        $listeners['modifier:replace'] = 'onModifierReplacement';
        $arguments->merge(['listeners' => $listeners]);

        $loader = $arguments->get('loader', []);
        $loader['onClassKept'] = [__('Loading from PHP modifier')];
        $loader['onModifierAdded'] = [__('Adding from PHP modifier')];
        $arguments->merge(['loader' => $loader]);
    }
}
