<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

class Mechanisms extends ServiceType
{
    function boot(): ServiceType
    {
        parent::boot();

        foreach ($this->items as $accessor => $mechanism) {
            $this->items[$accessor]['type']->boot();
        }

        return $this;
    }
}
