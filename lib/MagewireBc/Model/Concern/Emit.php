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

trait Emit
{
    use HandlesEvents;

    /**
     * @param array<string, mixed> $params
     */
    public function emit(string $event, $params = []): Event
    {
        return $this->dispatch($event, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function emitUp(string $event, $params = []): Event
    {

    }

    /**
     * Only emit an event on the component that fired the event.
     *
     * @param array<string, mixed> $params
     */
    public function emitSelf(string $event, $params = []): Event
    {

    }

    /**
     * Only emit an event to other components of the same type.
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
     * @param array<string, mixed> $params
     */
    public function emitToRefresh(string $name, $params = []): Event
    {

    }

    /**
     * Refresh all parents.
     *
     * @param array<string, mixed> $params
     */
    public function emitToRefreshUp($params = []): Event
    {

    }
}
