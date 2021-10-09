<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Model\Element\Event;

/**
 * Trait Emit
 * @package Magewirephp\Magewire\Model\Concern
 */
trait Emit
{
    /** @var Event[] $eventQueue */
    protected $eventQueue = [];

    /**
     * @return array
     */
    public function getEventQueue(): array
    {
        return $this->eventQueue;
    }

    /**
     * @param string $event
     * @param ...$params
     * @return Event
     */
    public function emit(string $event, ...$params): Event
    {
        return $this->eventQueue[] = new Event($event, $params);
    }

//    /**
//     * @param string $event
//     * @param ...$params
//     * @return Event
//     */
//    public function emitUp(string $event, ...$params): Event
//    {
//        return $this->emit($event, ...$params)->up();
//    }

    /**
     * Only emit an event on the component that fired the event.
     *
     * @param string $event
     * @param ...$params
     * @return Event
     */
    public function emitSelf(string $event, ...$params): Event
    {
        return $this->emit($event, ...$params)->self();
    }

    /**
     * Only emit an event to other components of the same type.
     *
     * @param string $name
     * @param string $event
     * @param ...$params
     * @return Event
     */
    public function emitTo(string $name, string $event, ...$params): Event
    {
        return $this->emit($event, ...$params)->component($name);
    }

    /**
     * Only emit a "refresh" event to other components of the same type.
     *
     * @param string $name
     * @return Event
     */
    public function emitToRefresh(string $name): Event
    {
        return $this->emitTo($name, 'refresh');
    }
}
