<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Enums;

enum ServiceTypeItemBootMode: int
{
    case LAZY = 10;
    case PERSISTENT = 20;
    case ALWAYS = 30;

    // @todo Something to look into later if this is something that is desired.
    //case DYNAMIC = 1; // Boots only when specific runtime conditions are satisfied.

    /**
     * Returns true when the boot mode is persistent.
     */
    public function isPersistent(): bool
    {
        return $this->is(self::PERSISTENT);
    }

    /**
     * Returns true when the boot mode is lazy.
     */
    public function isLazy(): bool
    {
        return $this->is(self::LAZY);
    }

    public function isAlways(): bool
    {
        return $this->is(self::ALWAYS);
    }

    /**
     * Returns true when the current case matches the given case.
     */
    public function is(self $case): bool
    {
        return $this === $case;
    }

    public function isHigherThan(self $case): bool
    {
        return $this->value > $case->value;
    }

    public function isHigherThanOrEqual(self $case): bool
    {
        return $this->value >= $case->value;
    }

    public function isLowerThan(self $case): bool
    {
        return $this->value < $case->value;
    }

    public function isLowerThanOrEqual(self $case): bool
    {
        return $this->value <= $case->value;
    }

    /**
     * Returns the lowercase name of the current case.
     */
    public function name(): string
    {
        return strtolower($this->name);
    }

    /**
     * Returns the default boot mode for any service type item.
     */
    public static function default(): self
    {
        return self::ALWAYS;
    }

    /**
     * Returns the given value if it exists or returns the default when it doesn't.
     */
    public static function try(mixed $value = null, self|null $fallback = null): self
    {
        if (is_numeric($value)) {
            $value = (int) $value;
        }

        return (self::exists($value)
            ? self::tryFrom($value)
            : null) ?? $fallback ?? self::default();
    }

    public static function exists(mixed $value): bool
    {
        return is_int($value) && self::tryFrom($value) !== null;
    }

    /**
     * Returns the case with the highest integer value.
     */
    public static function highest(): self
    {
        return self::from(max(array_column(self::cases(), 'value')));
    }

    /**
     * Returns the case with the lowest integer value.
     */
    public static function lowest(): self
    {
        return self::from(min(array_column(self::cases(), 'value')));
    }
}
