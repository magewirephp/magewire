<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

class Random
{
    static public function string(int $length = 6): string
    {
        return strtolower(bin2hex(random_bytes($length)));
    }

    static public function integer(int $min = 00001, int $max = 99999): int
    {
        return mt_rand($min, $max <= $min ? $min * 10 : $max);
    }
}
