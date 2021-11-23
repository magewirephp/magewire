<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

/**
 * @api
 */
interface WireableInterface
{
    /**
     * @return array|string|int|bool
     */
    public function wire();

    /**
     * @param array|string|int|bool $value
     * @return mixed
     */
    public static function dewire($value);
}
