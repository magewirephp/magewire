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
    static public function string(int $length = 6, bool $uppercased = true): string
    {
        $characters = self::alphabetical((int) ceil($length / 2), $uppercased) . self::integer($length);
        $characters = str_shuffle(str_repeat($characters, (int) ceil($length / strlen($characters))));

        return substr($characters, 0, $length);
    }

    static public function integer(int $min = 00001, int $max = 99999): int
    {
        return mt_rand($min, $max <= $min ? $min * 10 : $max);
    }

    static public function alphabetical(int $length = 6, bool $uppercased = false): string
    {
        $characters = $uppercased ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : 'abcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle(str_repeat($characters, (int) ceil($length / 52))), 0, $length);
    }
}
