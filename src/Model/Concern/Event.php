<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

/**
 * Trait Event.
 */
trait Event
{
    /** @var array */
    protected $listeners = [];

    /**
     * @return array
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }
}
