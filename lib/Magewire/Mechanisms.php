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

class Mechanisms extends ServiceType
{
    protected function callback(): callable
    {
        return static function (object $type) {
            $type->boot();
        };
    }

    protected function getServiceTypeItemBootModeFallback(): ServiceTypeItemBootMode
    {
        return ServiceTypeItemBootMode::ALWAYS;
    }
}
