<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

trait BrowserEvent
{
    protected $dispatchQueue = [];

    public function getBrowserEvents(): array
    {
        return $this->dispatchQueue;
    }

    public function dispatchBrowserEvent($event, $data = null): void
    {
        $this->dispatchQueue[] = compact('event', 'data');
    }
}
