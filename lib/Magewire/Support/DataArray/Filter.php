<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\DataArray;

use Countable;
use Exception;
use Magewirephp\Magewire\Support\DataArray;

enum Filter
{
    case ALL;
    case NONE;
    case STRINGS;
    case INTEGERS;
    case FLOATS;
    case BOOLEANS;
    case ARRAYS;
    case OBJECTS;
    case NULLS;
    case NUMERIC;
    case SCALAR;
    case SERIALIZABLE;
    case CALLABLE;
    case ITERABLE;
    case COUNTABLE;
    case JSON_ENCODABLE;

    public function get(): callable
    {
        return match($this) {
            self::ALL => fn ($value) => true,
            self::NONE => fn ($value) => false,

            self::STRINGS => fn ($value) => is_string($value),
            self::INTEGERS => fn ($value) => is_int($value),
            self::FLOATS => fn ($value) => is_float($value),
            self::BOOLEANS => fn ($value) => is_bool($value),
            self::ARRAYS => fn ($value) => is_array($value),
            self::OBJECTS => fn ($value) => is_object($value),
            self::NULLS => fn ($value) => is_null($value),
            self::NUMERIC => fn ($value) => is_numeric($value),
            self::SCALAR => fn ($value) => is_scalar($value),
            self::CALLABLE => fn ($value) => is_callable($value),
            self::ITERABLE => fn ($value) => is_iterable($value),

            self::COUNTABLE => fn ($value) => $value instanceof Countable,

            self::SERIALIZABLE => fn ($value) => $this->isSerializable($value),
            self::JSON_ENCODABLE => fn ($value) => $this->isJsonEncodable($value),
        };
    }

    /**
     * Combine multiple filters with OR logic.
     */
    public static function any(self ...$filters): callable
    {
        return function($value) use ($filters) {
            if ($value instanceof DataArray) {
                $value = $value->all();
            }

            foreach ($filters as $filter) {
                $filter = $filter->get();

                if ($filter($value)) {
                    return true;
                }
            }
            return false;
        };
    }

    /**
     * Combine multiple filters with AND logic.
     */
    public static function only(self ...$filters): callable
    {
        return function($value) use ($filters) {
            if ($value instanceof DataArray) {
                $value = $value->all();
            }

            foreach ($filters as $filter) {
                $filter = $filter->get();

                if ($filter($value)) {
                    return false;
                }
            }
            return true;
        };
    }

    private function isSerializable($value): bool
    {
        if (is_scalar($value) || is_null($value)) {
            return true;
        }
        if (is_array($value)) {
            return array_reduce($value, fn ($carry, $item) => $carry && $this->isSerializable($item), true);
        }

        return false;
    }

    private function isJsonEncodable($value): bool
    {
        try {
            json_encode($value);
        } catch (Exception) {
            return false;
        }

        return true;
    }
}
