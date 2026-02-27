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
trait BrowserEvent
{
    /**
     * @todo Restore HandlesEvents trait within Component once Browser Events feature is fully removed.
     *       Temporarily commented out during Browser Events deprecation.
     *
     * @see Component
     */
    use HandlesEvents;

    /**
     * @deprecated Has been replaced with the "listeners" property.
     * @see listeners
     */
    protected $dispatchQueue = [];

    /**
     * @deprecated Has been replaced with a universal "getListeners" method.
     * @see getListeners()
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
        $this->dispatch($event, ...$data);
    }
}
