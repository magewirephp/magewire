<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireEvents;

use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;

use function Magewirephp\Magewire\invade;
use function Magewirephp\Magewire\store;

class SupportMagewireEvents extends SupportEvents
{
    public static function getComponentListeners($component)
    {
        $listeners = static::mergeListenerSources(invade($component)->getListeners(), store($component)->get('listenersFromAttributes', []), static::getLayoutListeners($component));

        return static::replaceDynamicEventNamePlaceholders($listeners, $component);
    }

    /**
     * Return the per-placement listener overlay declared through the
     * `magewire:listeners` layout argument.
     */
    protected static function getLayoutListeners($component): array
    {
        $resolver = $component->magewireResolver();

        if ($resolver === null) {
            return [];
        }

        $listeners = $resolver->arguments()->get('listeners', []);

        return is_array($listeners) ? $listeners : [];
    }

    /**
     * Normalize each source to event => method before layering it. A false or
     * null value is a tombstone for the same event in an earlier source.
     */
    protected static function mergeListenerSources(array ...$sources): array
    {
        $listeners = [];

        foreach ($sources as $source) {
            foreach ($source as $event => $method) {
                $event = is_numeric($event) ? $method : $event;

                if ($method === false || $method === null) {
                    unset($listeners[$event]);
                    continue;
                }

                $listeners[$event] = $method;
            }
        }

        return $listeners;
    }
}
