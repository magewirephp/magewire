<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
abstract class FlakeValueBag implements Countable, IteratorAggregate
{
    /**
     * @param array<TKey, TValue> $values
     */
    public function __construct(
        private readonly array $values = []
    ) {
    }

    /**
     * Return a value by key.
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Determine whether a key exists.
     */
    public function has(string|int $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Return all values.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Count all values.
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Iterate all values.
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }
}
