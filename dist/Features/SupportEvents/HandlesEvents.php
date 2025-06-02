<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportEvents;

use function Magewirephp\Magewire\store;
trait HandlesEvents
{
    protected $listeners = [];
    protected function getListeners()
    {
        return $this->listeners;
    }
    public function dispatch($event, ...$params)
    {
        $event = new Event($event, $params);
        store($this)->push('dispatched', $event);
        return $event;
    }
}