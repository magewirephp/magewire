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
     * @param array<string, mixed> $params
     */
    public function emit(string $event, $params = []): Event
    {
        return $this->dispatch($event, ...is_array($params) ? $params : [$params]);
    }

    /**
     * In v3 events bubble up by default, so emitting "up" is just a dispatch.
     *
     * @deprecated Use dispatch() instead.
     *
     * @param array<string, mixed> $params
     */
    public function emitUp(string $event, $params = []): Event
    {
        return $this->dispatch($event, ...is_array($params) ? $params : [$params]);
    }

    /**
     * Only emit an event on the component that fired the event.
     *
     * @deprecated Use dispatch()->self() instead.
     *
     * @param array<string, mixed> $params
     */
    public function emitSelf(string $event, $params = []): Event
    {
        return $this->dispatch($event, ...is_array($params) ? $params : [$params])->self();
    }

    /**
     * Only emit an event to other components of the same type.
     *
     * @deprecated Use dispatch()->to() instead.
     *
     * @param array<string, mixed> $params
     */
    public function emitTo(string $name, string $event, $params = []): Event
    {
        return $this->emit($event, $params)->to($name);
    }

    /**
     * Only emit a "refresh" event to other components of the same type.
     *
     * @deprecated Use dispatch('$refresh')->to() instead.
     *
     * @param array<string, mixed> $params
     */
    public function emitToRefresh(string $name, $params = []): Event
    {
        return $this->dispatch('$refresh', ...is_array($params) ? $params : [$params])->to($name);
    }

    /**
     * Refresh all parents. In v3 events bubble up by default, so a
     * dispatched "$refresh" reaches parent components automatically.
     *
     * @deprecated Use dispatch('$refresh') instead.
     *
     * @param array<string, mixed> $params
     */
    public function emitToRefreshUp($params = []): Event
    {
        return $this->dispatch('$refresh', ...is_array($params) ? $params : [$params]);
    }
}
