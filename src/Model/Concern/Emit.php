<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Model\Element\Event;

trait Emit
{
    /** @var Event[] */
    protected $eventQueue = [];

    /**
     * @return Event[]
     */
    public function getEventQueue(): array
    {
        return $this->eventQueue;
    }

    /**
     * @param string $event
     * @param array<string, mixed> $params
     * @return Event
     */
    public function emit(string $event, $params = []): Event
    {
        return $this->eventQueue[] = new Event(
            $event,
            $this->supportLegacySyntax($params, array_slice(func_get_args(), 1))
        );
    }

    /**
     * @param string $event
     * @param array<string, mixed> $params
     * @return Event
     */
    public function emitUp(string $event, $params = []): Event
    {
        return $this->emit(
            $event,
            $this->supportLegacySyntax($params, array_slice(func_get_args(), 1))
        )->up();
    }

    /**
     * Only emit an event on the component that fired the event.
     *
     * @param string $event
     * @param array<string, mixed> $params
     * @return Event
     */
    public function emitSelf(string $event, $params = []): Event
    {
        return $this->emit(
            $event,
            $this->supportLegacySyntax($params, array_slice(func_get_args(), 1))
        )->self();
    }

    /**
     * Only emit an event to other components of the same type.
     *
     * @param string $name
     * @param string $event
     * @param array<string, mixed> $params
     * @return Event
     */
    public function emitTo(string $name, string $event, $params = []): Event
    {
        return $this->emit(
            $event,
            $this->supportLegacySyntax($params, array_slice(func_get_args(), 2))
        )->component($name);
    }

    /**
     * Only emit a "refresh" event to other components of the same type.
     *
     * @param string $name
     * @param array<string, mixed> $params
     * @return Event
     */
    public function emitToRefresh(string $name, $params = []): Event
    {
        return $this->emitTo(
            $name,
            'refresh',
            $this->supportLegacySyntax($params, array_slice(func_get_args(), 1))
        );
    }

    /**
     * Refresh all parents.
     *
     * @param array<string, mixed> $params
     * @return Event
     */
    public function emitToRefreshUp($params = []): Event
    {
        return $this->emitUp(
            'refresh',
            $this->supportLegacySyntax($params, func_get_args())
        );
    }

    /**
     * Support legacy emits until major update.
     *
     * @param $firstArgs
     * @param $restArgs
     * @return array
     */
    protected function supportLegacySyntax($firstArgs, $restArgs): array
    {
        if (! is_array($firstArgs) || count($restArgs) > 1) {
            return $restArgs;
        }

        return $firstArgs;
    }
}
