<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use ArrayAccess;

class DataArray extends DataCollection implements ArrayAccess
{
    public function offsetExists($offset): bool
    {
        return $this->isset($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        is_string($offset) || is_int($offset) ? $this->set($offset, $value) : $this->push($value);
    }

    public function offsetUnset($offset): void
    {
        $this->unset($offset);
    }
}
