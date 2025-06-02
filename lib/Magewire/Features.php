<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

class Features extends ServiceType
{
    function __construct(
        private readonly ComponentHookRegistry $componentHookRegistry,
        array $items = []
    ) {
        parent::__construct($items);
    }

    function boot() : ServiceType
    {
        parent::boot();

        foreach ($this->items as $accessor => $feature) {
            $this->componentHookRegistry::register($this->items[$accessor]['type']);
        }

        $this->componentHookRegistry::boot();
        return $this;
    }
}
