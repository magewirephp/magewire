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
            if (method_exists($type, 'boot')) {
                $type->boot();
            }
        };
    }

    protected function getBootModeFallback(): ServiceTypeItemBootMode
    {
        return ServiceTypeItemBootMode::LAZY;
    }
}
