<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\Management;

use Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout\LayoutLifecycle;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\Factory;

class LayoutLifecycleManager
{
    public function __construct(
        private DataCollection|null $lifecycles
    ) {
    }

    public function target(string $area): LayoutLifecycle
    {
        return $this->lifecycles()->get($area, Factory::create(LayoutLifecycle::class), true);
    }

    private function lifecycles(): DataCollection
    {
        return $this->lifecycles ??= Factory::create(DataCollection::class);
    }
}
