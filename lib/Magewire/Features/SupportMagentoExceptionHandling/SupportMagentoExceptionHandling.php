<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoExceptionHandling;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Throwable;
use function Magewirephp\Magewire\on;

class SupportMagentoExceptionHandling extends ComponentHook
{
    function provide(): void
    {
        on('exception', function (Component $component, Throwable $exception, callable $stopPropagation) {
            // TBD
        });
    }
}
