<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\HandlesEvents;

/**
 * @deprecated Has been replaced with the HandlesEvents trait.
 * @see HandlesEvents
 */
trait DispatchBrowserEvent
{
    /**
     * @todo Restore HandlesEvents trait within Component once dispatchBrowserEvent() compatibility is fully removed.
     *       Temporarily included here during dispatchBrowserEvent() deprecation.
     *
     * @see Component
     */
    use HandlesEvents;

    /**
     * @deprecated Use dispatch() for new events; this queue retains legacy dispatchBrowserEvent() payloads.
     * @see dispatch()
     */
    protected $dispatchQueue = [];

    /**
     * @deprecated Exposes the dispatchBrowserEvent() queue to compatibility modules.
     * @see dispatch()
     */
    public function getBrowserEvents(): array
    {
        return $this->dispatchQueue;
    }

    /**
     * @deprecated Has been replaced with a universal "dispatch" method.
     * @see dispatch()
     */
    public function dispatchBrowserEvent($event, $data = null): void
    {
        // Keep legacy payloads unchanged for compatibility modules to deliver separately from dispatch().
        $this->dispatchQueue[] = compact('event', 'data');
    }
}
