<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireEvents;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\ComponentHookRegistry;
use Magewirephp\Magewire\Exceptions\EventHandlerDoesNotExist;
use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutArgumentOverlay;

use function Magewirephp\Magewire\before;
use function Magewirephp\Magewire\store;

class SupportMagewireEvents extends ComponentHook
{
    public static function provide(): void
    {
        before('call', static function ($component, $method, $params): void {
            if ($method !== '__dispatch') {
                return;
            }

            $hook = ComponentHookRegistry::getHook($component, self::class);

            if ($hook instanceof self && is_string($params[0] ?? null)) {
                $hook->ensureListenerIsNotRemoved($params[0]);
            }
        });
    }

    public function skip(): bool
    {
        return ComponentHookRegistry::getHook($this->component(), SupportEvents::class) === null;
    }

    public function boot(): void
    {
        $component = $this->component();
        $fromAttributes = store($component)->get('listenersFromAttributes', []);

        store($component)->set('listenersFromAttributes', LayoutArgumentOverlay::apply(
            static::normalizeListeners($fromAttributes),
            static::normalizeListeners(static::getLayoutListeners($component)),
            [false, null]
        ));
    }

    public function dehydrate(ComponentContext $context): void
    {
        if (! $context->isMounting() || ! $context->hasEffect('listeners')) {
            return;
        }

        $removed = $this->getRemovedListenerNames();
        $listeners = array_values(array_filter($context->getEffects()->getData('listeners', []), static fn ($listener) => ! in_array($listener, $removed, true)));

        $listeners === [] ? $context->getEffects()->exclude('listeners') : $context->addEffect('listeners', $listeners);
    }

    /**
     * Return the per-placement listener overlay declared through the
     * `magewire:listeners` layout argument.
     */
    protected static function getLayoutListeners($component): array
    {
        $listeners = LayoutArgumentOverlay::get($component, 'listeners', []);

        return is_array($listeners) ? $listeners : [];
    }

    protected static function normalizeListeners(array $listeners): array
    {
        $normalized = [];

        foreach ($listeners as $event => $method) {
            $event = is_numeric($event) ? $method : $event;
            $normalized[$event] = $method;
        }

        return $normalized;
    }

    private function ensureListenerIsNotRemoved(string $listener): void
    {
        if (in_array($listener, $this->getRemovedListenerNames(), true)) {
            throw new EventHandlerDoesNotExist($listener);
        }
    }

    private function getRemovedListenerNames(): array
    {
        $component = $this->component();
        $tombstones = LayoutArgumentOverlay::removed(static::normalizeListeners(static::getLayoutListeners($component)), [false, null]);
        $tombstones = SupportEvents::replaceDynamicEventNamePlaceholders($tombstones, $component);

        return array_keys($tombstones);
    }
}
