<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Features\SupportEvents\HandlesEvents;

/**
 * @deprecated Livewire v2 emit API. Use dispatch() and its modifiers
 *             (->self(), ->to()) instead.
 */
trait Emit
{
    use HandlesEvents;

    /**
     * @deprecated Use dispatch() instead.
     *
     * @param mixed $params An array holding the listener arguments, or a single argument. Further arguments
     *                      are passed to the listener one by one.
     */
    public function emit(string $event, $params = []): Event
    {
        return $this->dispatch($event, ...$this->magewireEmitParams(array_slice(func_get_args(), 1)));
    }

    /**
     * In v3 events bubble up by default, so emitting "up" is just a dispatch.
     *
     * @deprecated Use dispatch() instead.
     *
     * @param mixed $params An array holding the listener arguments, or a single argument. Further arguments
     *                      are passed to the listener one by one.
     */
    public function emitUp(string $event, $params = []): Event
    {
        return $this->dispatch($event, ...$this->magewireEmitParams(array_slice(func_get_args(), 1)));
    }

    /**
     * Only emit an event on the component that fired the event.
     *
     * @deprecated Use dispatch()->self() instead.
     *
     * @param mixed $params An array holding the listener arguments, or a single argument. Further arguments
     *                      are passed to the listener one by one.
     */
    public function emitSelf(string $event, $params = []): Event
    {
        return $this->dispatch($event, ...$this->magewireEmitParams(array_slice(func_get_args(), 1)))->self();
    }

    /**
     * Only emit an event to other components of the same type.
     *
     * @deprecated Use dispatch()->to() instead.
     *
     * @param mixed $params An array holding the listener arguments, or a single argument. Further arguments
     *                      are passed to the listener one by one.
     */
    public function emitTo(string $name, string $event, $params = []): Event
    {
        return $this->dispatch($event, ...$this->magewireEmitParams(array_slice(func_get_args(), 2)))->to($name);
    }

    /**
     * Only emit a "refresh" event to other components of the same type.
     *
     * @deprecated Use dispatch('$refresh')->to() instead.
     *
     * @param mixed $params An array holding the listener arguments, or a single argument. Further arguments
     *                      are passed to the listener one by one.
     */
    public function emitToRefresh(string $name, $params = []): Event
    {
        return $this->dispatch('$refresh', ...$this->magewireEmitParams(array_slice(func_get_args(), 1)))->to($name);
    }

    /**
     * Refresh all parents. In v3 events bubble up by default, so a
     * dispatched "$refresh" reaches parent components automatically.
     *
     * @deprecated Use dispatch('$refresh') instead.
     *
     * @param mixed $params An array holding the listener arguments, or a single argument. Further arguments
     *                      are passed to the listener one by one.
     */
    public function emitToRefreshUp($params = []): Event
    {
        return $this->dispatch('$refresh', ...$this->magewireEmitParams(func_get_args()));
    }

    /**
     * Returns the listener arguments for the arguments an emit method received after the event (and target) name.
     *
     * Like in v1, several arguments, e.g. emit('event', 'foo', 'bar'), are passed to the listener one by one.
     * A single array is passed as the listener arguments, a single other value, null included, as the only argument.
     */
    private function magewireEmitParams(array $arguments): array
    {
        if (count($arguments) > 1) {
            return $arguments;
        }

        $params = array_key_exists(0, $arguments) ? $arguments[0] : [];

        return is_array($params) ? $params : [$params];
    }
}
