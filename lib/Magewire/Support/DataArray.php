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
    private array $snapshots = [];

    /**
     * Maps (renames) multiple keys in the collection based on a mapping array.
     *
     * Iterates through the provided mapping array and renames keys from the source key
     * to the target key. Only processes mappings where the source key is a string or integer
     * and the target key is a string. Invalid mappings are silently skipped.
     *
     * @param array<string|int, string|int> $map
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
     * Replaces the value of an existing key in the collection.
     *
     * Only updates the value if the key already exists in the collection.
     * If the key doesn't exist, no action is taken and the collection remains unchanged.
     */
    public function replace(string|int $key, $value): static
    {
        if ($this->isset($key)) {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Rename an argument if it exists.
     */
    public function rename(string|int $from, string|int $to): static
    {
        return $this->copy($from, $to, true);
    }

    /**
     * Executes a callback function for each item in the collection.
     *
     * Iterates through all items and calls the provided callback with the collection instance,
     * the item's value, and the item's key. This method is useful for performing side effects
     * or operations that don't need to modify the collection structure.
     */
    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $value) {
            $callback($this, $value, $key);
        }

        return $this;
    }

    /**
     * Copies the value from one key to another key in the collection.
     *
     * Creates a duplicate of the value at the source key and assigns it to the target key.
     * Only performs the copy if the source key exists in the collection. Optionally removes
     * the original key after copying (effectively becoming a move operation).
     */
    public function copy(string|int $from, string|int $to, bool $unset = false): static
    {
        if ($this->isset($from)) {
            $this->set($to, $this->items[$from]);

            if ($unset) {
                $this->unset($from);
            }
        }

        return $this;
    }

    public function unset(string|int ...$keys): static
    {
        foreach ($keys as $key) {
            if ($this->isset($key)) {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function set(string|int|array $name, $value): static
    {
        if ($this->isset($name)) {
            return $this;
        }

        // Substitute parameter references (e.g., ':name' becomes the value of 'name').
        if (is_string($value) && str_starts_with($value, ':') && $this->isset(substr($value, 1))) {
            $value = $this->get(substr($value, 1));
        }

        $this->items[$name] = $value;
        return $this;
    }

    public function put(string|int|array $name, $value): static
    {
        if ($this->isset($name)) {
            $this->items[$name] = $value;
        }

        return $this;
    }

    /**
     * Merges an array of items into the collection.
     *
     * Iterates through the provided array and adds each key-value pair to the collection.
     * Existing keys will be overwritten with the new values from the merged array.
     */
    public function merge(array $items): static
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function fill(array $items, bool $merge = true): static
    {
        if ($merge) {
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
     * Returns all collection items.
     *
     * @return array<string|int, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Returns a filtered items collection.
     */
    public function fetch(callable $filter): array
    {
        return $this->filter($filter, false);
    }

    /**
     * Extracts values from the collection for the specified keys.
     *
     * Returns an associative array containing only the specified keys and their
     * corresponding values from the collection. If a key doesn't exist in the
     * collection, a default value can be provided; otherwise the key will be
     * omitted from the result.
     */
    public function pluck(array $keys, array $defaults = []): array
    {
        $result = [];

        foreach ($keys as $key) {
            if ($this->isset($key)) {
                $result[$key] = $this->get($key);
            } elseif (array_key_exists($key, $defaults)) {
                $result[$key] = $defaults[$key];
            }
        }

        return $result;
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
     * Returns a filtered array and optionally apply it as the latest state.
     */
    public function filter(callable $filter, bool $apply = true): array
    {
        $items = array_filter($this->items, $filter, ARRAY_FILTER_USE_BOTH);

        if ($apply) {
            $this->fill($items);
        }

        return $items;
    }

    /**
     * Retrieves the value for the given name, returning the default if it doesn't exist,
     * with an option to set the default before returning.
     */
    public function get(string|int $name, $default = null, bool $set = false)
    {
        return $this->items[$name] ?? ($set ? $this->default($name, $default)->get($name) : $default);
    }

    /**
     * Reset the array to a given state.
     */
    public function reset(): static
    {
        $i = count($this->snapshots);

        if ($i === 0) {
            $this->fill([]);
        }

        return $this->revert($i);
    }

    /**
     * Ensure a default value is set for a given key, guaranteeing its presence
     * regardless of whether it previously existed.
     */
    public function default(string|int $key, $value): static
    {
        if ($this->isset($key)) {
            return $this;
        }

        return $this->set($key, $value);
    }

    /**
     * Clears the collection.
     */
    public function clear(callable|null $filter = null): static
    {
        $this->items = $filter ? array_filter($this->items, $filter, ARRAY_FILTER_USE_BOTH) : [];

        return $this;
    }

    /**
     * Returns the number of collection items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Take a snapshot of the current data state.
     */
    public function snapshot(): static
    {
        $this->snapshots[] = [
            'items' => $this->items,
            'snapshots' => $this->snapshots,
        ];

        return $this;
    }

    /**
     * Revert to a specific taken snapshot.
     */
    public function revert(int $offset = 1): static
    {
        $i = count($this->snapshots);

        if ($i < $offset) {
            return $this;
        }

        $latest = $this->snapshots[$i - $offset];

        $this->items = $latest['items'];
        $this->snapshots = $latest['snapshots'];

        return $this;
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
