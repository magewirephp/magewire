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
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Traversable;

class DataArray implements ArrayAccess, Countable, IteratorAggregate
{
    use WithFactory;

    private array $items = [];

    /**
     * Map argument values to specific argument names.
     *
     * @param array<string, string> $map
     */
    public function map(array $map): static
    {
        foreach ($map as $from => $to) {
            if ((is_string($from) || is_int($from)) && is_string($to)) {
                $this->rename($from, $to);
            }
        }

        return $this;
    }

    /**
     * Rename an argument if it exists.
     */
    public function rename(string $from, string $to): static
    {
        return $this->copy($from, $to, true);
    }

    /**
     * Copy an arguments value into a new argument.
     */
    public function copy(string $from, string $to, bool $unset = false): static
    {
        if ($this->isset($from)) {
            $this->set($to, $this->items[$from]);

            if ($unset) {
                $this->unset($from);
            }
        }

        return $this;
    }

    public function unset(string $name): static
    {
        if ($this->isset($name)) {
            unset($this->items[$name]);
        }

        return $this;
    }

    public function set(string|int|array $name, $value): static
    {
        if ($this->isset($name)) {
            return $this;
        }

        $this->items[$name] = $value;
        return $this;
    }

    public function merge(array $items): static
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function fill(array $items): static
    {
        if ($this->count() !== 0) {
            return $this->merge($items);
        }

        $this->items = $items;

        return $this;
    }

    public function isset(string|int $argument): bool
    {
        return isset($this->items[$argument]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Returns all item values.
     */
    public function values(): array
    {
        return array_values($this->items);
    }

    /**
     * Returns all item keys.
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Returns a filtered array.
     */
    public function filter(callable $filter, ?array $array = null): array
    {
        return array_filter($array ?? $this->items, $filter);
    }

    /**
     * Retrieves the value for the given name, returning the default if it doesn't exist,
     * with an option to set the default before returning.
     */
    public function get(string $name, $default = null, bool $set = false)
    {
        return $this->items[$name] ?? ($set ? $this->default($name, $default)->get($name) : $default);
    }

    /**
     * Reset the array to a given state.
     */
    public function reset(array $value): static
    {
        $this->items = $value;

        return $this;
    }

    /**
     * Ensure a default value is set for a given key, guaranteeing its presence
     * regardless of whether it previously existed.
     */
    public function default(string $name, $value): static
    {
        if ($this->isset($name)) {
            return $this;
        }

        return $this->set($name, $value);
    }

    /**
     * Clears the array.
     */
    public function clear(): static
    {
        $this->items = [];

        return $this;
    }

    /**
     * Returns the number of items.
     */
    public function count(): int
    {
        return count($this->items);
    }

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
        if (is_string($offset) || is_int($offset)) {
            $this->set($offset, $value);
        }

        $this->items[] = $value;
    }

    public function offsetUnset($offset): void
    {
        $this->unset($offset);
    }

    public function getIterator(): Traversable
    {
        return ObjectManager::getInstance()->create(ArrayIterator::class, ['array' => $this->items]);
    }
}
