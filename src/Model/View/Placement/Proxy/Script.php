<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Placement\Proxy;

use Magewirephp\Magewire\Model\View\PlacementBuffer;
use Magewirephp\Magewire\Model\View\PlacementProxyInterface;
use Magewirephp\Magewire\Model\View\PlacementRegistry;

class Script implements PlacementProxyInterface
{
    /**
     * Add rendered script-fragment output to a named script placement.
     *
     * Called through {@see PlacementRegistry::__call()} as
     * `$registry->script($name, $content)`.
     *
     * @param string $arguments[0] The script placement name, for example `default`.
     * @param string|\Stringable $arguments[1] The already-rendered script fragment output to append.
     */
    public function __invoke(PlacementRegistry $registry, mixed ...$arguments): PlacementBuffer
    {
        return $registry->add('script', ...$arguments);
    }
}
