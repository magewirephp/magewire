<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

/**
 * Sends events from the deprecated dispatchBrowserEvent() apart from Livewire's dispatches,
 * so the frontend can deliver their payload in the Magewire 1 shape.
 */
class SupportBrowserEvents extends ComponentHook
{
    public function dehydrate(ComponentContext $context): void
    {
        $browserEvents = $this->component->getBrowserEvents();

        if ($browserEvents !== []) {
            $context->pushEffect('bc', $browserEvents, 'dispatches');
        }
    }
}
