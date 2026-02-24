<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Enums;

enum RequestMode: int
{
    case UNDEFINED = 0;
    case PRECEDING = 1;
    case SUBSEQUENT = 2;

    public function isUndefined(): bool
    {
        return $this === RequestMode::UNDEFINED;
    }

    public function isSubsequent(): bool
    {
        return $this === RequestMode::SUBSEQUENT;
    }

    public function isPreceding(): bool
    {
        return $this === RequestMode::PRECEDING;
    }
}
