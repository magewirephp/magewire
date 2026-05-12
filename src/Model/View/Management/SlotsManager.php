<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Management;

use Magewirephp\Magewire\Model\View\SlotsRegistry;

class SlotsManager
{
    public function __construct(
        private readonly SlotsRegistry $slotsRegistry
    ) {
    }

    public function registry(): SlotsRegistry
    {
        return $this->slotsRegistry;
    }
}
