<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\DataCollection;

use Countable;
use Exception;

enum TypeFilter
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
            self::ALL => static fn ($value) => true,
            self::NONE => static fn ($value) => false,

            self::STRINGS => static fn ($value) => is_string($value),
            self::INTEGERS => static fn ($value) => is_int($value),
            self::FLOATS => static fn ($value) => is_float($value),
            self::BOOLEANS => static fn ($value) => is_bool($value),
            self::ARRAYS => static fn ($value) => is_array($value),
            self::OBJECTS => static fn ($value) => is_object($value),
            self::NULLS => static fn ($value) => is_null($value),
            self::NUMERIC => static fn ($value) => is_numeric($value),
            self::SCALAR => static fn ($value) => is_scalar($value),
            self::CALLABLE => static fn ($value) => is_callable($value),
            self::ITERABLE => static fn ($value) => is_iterable($value),

            self::COUNTABLE => static fn ($value) => $value instanceof Countable,

            self::SERIALIZABLE => fn ($value) => $this->isSerializable($value),
            self::JSON_ENCODABLE => fn ($value) => $this->isJsonEncodable($value),
        };
    }

    /**
     * Combine multiple filters with OR logic.
     */
    public static function any(self ...$filters): callable
    {
        return static function($value) use ($filters) {
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
        return static function($value) use ($filters) {
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
