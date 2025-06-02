<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Containers;

/**
 * @internal Work in progress.
 */
class Redirect
{
    protected $component;

    public function to($path, $status = 302, $headers = [], $secure = null)
    {
        //
    }

    public function away($path, $status = 302, $headers = [])
    {
        //
    }

    public function with($key, $value = null)
    {
        //
    }

    public function component(\Magewirephp\Magewire\Component $component)
    {
        $this->component = $component;

        return $this;
    }

    public function response($to)
    {
        //
    }
}
