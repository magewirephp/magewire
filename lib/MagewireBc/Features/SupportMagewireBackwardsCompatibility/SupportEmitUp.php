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
use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

/**
 * Sends the events of the deprecated emitUp() and emitToRefreshUp() apart from Livewire's dispatches,
 * so the frontend can deliver them to the ancestors of the component only, as Magewire 1 did.
 *
 * @see backwards-compatibility-emit-up.phtml
 */
class SupportEmitUp extends ComponentHook
{
    public function dehydrate(ComponentContext $context): void
    {
        $events = array_map(static fn (Event $event) => $event->serialize(), $this->component->getAncestorEvents());

        if ($events !== []) {
            $context->pushEffect('bc', $events, 'ancestorDispatches');
        }
    }
}
