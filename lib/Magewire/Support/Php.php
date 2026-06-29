<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use InvalidArgumentException;

class Php
{
    /**
     * Encode a value as a single-quoted PHP string literal for generated code.
     */
    public static function stringLiteral(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

    /**
     * Encode a PHP value as source code for generated code.
     */
    public static function valueLiteral(mixed $value): string
    {
        return match (true) {
            is_string($value) => self::stringLiteral($value),
            is_int($value) => (string) $value,
            is_float($value) => self::floatLiteral($value),
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            is_array($value) => self::arrayLiteral($value),
            default => throw new InvalidArgumentException(sprintf('Unable to compile %s as a PHP literal.', get_debug_type($value)))
        };
    }

    /**
     * Encode a PHP array as short array syntax for generated code.
     */
    public static function arrayLiteral(array $value): string
    {
        $items = [];
        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            $items[] = $isList
                ? self::valueLiteral($item)
                : self::valueLiteral($key) . ' => ' . self::valueLiteral($item);
        }

        return '[' . implode(', ', $items) . ']';
    }

    /**
     * Convert a variable name to a PHP variable expression.
     */
    public static function variable(string $name): string
    {
        $name = ltrim($name, '$');

        if (! preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid PHP variable name [%s].', $name));
        }

        return '$' . $name;
    }

    /**
     * Wrap generated code in a PHP tag.
     */
    public static function tag(string $code): string
    {
        return "<?php {$code} ?>";
    }

    /**
     * Wrap an expression in a PHP echo tag.
     */
    public static function echoTag(string $expression): string
    {
        return self::tag("echo {$expression}");
    }

    private static function floatLiteral(float $value): string
    {
        if (! is_finite($value)) {
            throw new InvalidArgumentException('Unable to compile a non-finite float as a PHP literal.');
        }

        return var_export($value, true);
    }
}
