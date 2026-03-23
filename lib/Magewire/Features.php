<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;

class Features extends ServiceType
{
    function __construct(
        private readonly ComponentHookRegistry $componentHookRegistry,
        array $items = []
    ) {
        parent::__construct($items);
    }

    function boot(ServiceTypeItemBootMode|null $mode = null): bool
    {
        $booted = parent::boot($mode);

        if ($booted) {
            $this->componentHookRegistry::boot();
        }

        return $booted;
    }

    protected function callback(): callable
    {
        return function (object $type) {
            $this->componentHookRegistry::register($type);
        };
    }
}
