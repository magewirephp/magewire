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
use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Magewirephp\Magewire\Support\DataArray\Filter;
use Magewirephp\Magewire\Support\DataArray\Hook;
use Traversable;

class DataArray implements ArrayAccess, Countable, IteratorAggregate
{
    use WithFactory;

    /**
     * The collection's data items.
     *
     * @var array<string|int, mixed>
     */
    private array $items = [];

    /**
     * Categorized DataArray instances for organizing related data into logical groups.
     *
     * Each category maintains its own independent collection of items and is lazily
     * initialized when first accessed via categorize() or magic method calls.
     *
     * @var array<string, DataArray>
     */
    private array $subitems = [];

    /**
     * @var array<Hook, array<int, callable>>
     */
    private array $hooks = [];

    public function __construct(
        private int $level = 0,
        private string $name = 'root',
        private DataArray|null $parent = null
    ) {
        //
    }

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
    public function each(callable $callback, callable|Filter $filter = Filter::ALL): static
    {
        foreach ($this->fetch($filter) as $key => $value) {
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

    /**
     * Sets a value for the given key if it doesn't already exist.
     *
     * Only sets the value if the key is not already present in the collection.
     * If the key exists, the collection remains unchanged.
     *
     * Supports parameter references: if the value is a string starting with ':',
     * it will be replaced with the value of the referenced key. For example,
     * setting ':name' will use the value stored under 'name'.
     */
    public function set(string|int $name, $value): static
    {
        if ($this->isset($name)) {
            return $this;
        }

        $this->items[$name] = $value;
        return $this;
    }

    /**
     * Creates or retrieves a nested DataArray subset.
     *
     * Returns an existing subset if it has been previously created, or initializes
     * a new DataArray instance with an incremented nesting level. The subset name
     * is automatically converted to snake_case for consistent storage.
     *
     * Subsets are useful for creating hierarchical data structures where each level
     * maintains its own independent collection while tracking its depth and name.
     */
    public function subset(string|int $name, string $type = DataArray::class, array $arguments = []): DataArray
    {
        $name = Str::snake($name);

        $arguments = array_merge($arguments, [
            'parent' => $this,
            'level'  => $this->level + 1,
            'name'   => $name
        ]);

        return $this->subitems[$name] ??= $this->newInstance($arguments, $type);
    }

    /**
     * Returns all subsets.
     */
    public function subsets(): array
    {
        return $this->subitems;
    }

    /**
     * Replaces the value of an existing key in the collection.
     *
     * Only updates the value if the key already exists in the collection.
     * If the key doesn't exist, no action is taken and the collection remains unchanged.
     */
    public function put(string|int $name, $value): static
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

    public function isset(string|int $name): bool
    {
        return isset($this->items[$name]);
    }

    /**
     * Returns all collection items as an array.
     *
     * @return array<string|int, mixed>
     */
    public function all(): array
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            if ($value instanceof DataArray) {
                $value = $value->all();
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Returns collection items as json.
     */
    public function json(callable|null $filter = null): string
    {
        $items = $this->fetch(Filter::JSON_ENCODABLE);

        if ($filter) {
            return json_encode($this->filter($items, $filter));
        }

        return json_encode($items);
    }

    /**
     * Returns a filtered items collection.
     */
    public function fetch(callable|Filter $filter): array
    {
        return $this->filter($this->items, $filter);
    }

    /**
     * Filters an array using a callback or Filter instance.
     *
     * Applies the provided filter to each item in the array, passing both the value
     * and key to the filter function. Supports both custom callables and Filter
     * enum instances for predefined filtering logic.
     */
    public function filter(array $items, callable|Filter $filter): array
    {
        return array_filter($items, $filter instanceof Filter ? $filter->get() : $filter, ARRAY_FILTER_USE_BOTH);
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
     * Retrieves the value for the given name, returning the default if it doesn't exist,
     * with an option to set the default before returning.
     */
    public function get(string|int $name, $default = null, bool $set = false)
    {
        return $this->items[$name] ?? ($set ? $this->default($name, $default)->get($name) : $default);
    }

    public function reset(): static
    {
        $this->items = [];

        return $this;
    }

    /**
     * Destroy both the items and the subitems.
     */
    public function destroy(): static
    {
        $this->reset();
        $this->subitems = [];

        return $this;
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
     * Ensure the default values are set for the given keys, guaranteeing its presence
     * regardless of whether it previously existed.
     */
    public function defaults(array $defaults): static
    {
        foreach ($defaults as $key => $value) {
            if (is_string($key) || is_int($key)) {
                $this->default($key, $value);
            }
        }

        return $this;
    }

    /**
     * Clears the collection.
     */
    public function clear(callable|Filter $filter = Filter::NONE): static
    {
        $this->items = $this->fetch($filter);

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
     * Clones the current data items into a new collection object excluding the subsets.
     */
    public function snapshot(): DataArray
    {
        return $this->newInstance()->fill($this->all());
    }

    /**
     * Recursively applies a callback to this instance and all nested subsets.
     *
     * Behavior adapts based on the callback's return type:
     * - Chainable mode: Returns $this when callback returns $this, static instance, or null
     *   (useful for setters, fluent methods, or side-effect operations)
     * - Collection mode: Returns flat associative array when callback returns values
     *   (each result is keyed by the instance's name)
     *
     * In collection mode, all results are collected into a single flat array where
     * the key is each DataArray instance's name and the value is the callback's
     * return value for that instance. Results from parent and all nested subsets
     * are merged at the same level.
     *
     * @example $data->recursively(fn (DataArray $item) => $item->set('processed', true));
     * @example $data->recursively(fn (DataArray $item) => $item->count());
     * @example $data->recursively(fn (DataArray $item) => $item->get('value'));
     */
    public function recursively(callable $callback): static|array
    {
        $result  = $callback($this);
        $subsets = $this->subsets();

        // Chainable mode: callback returned nothing or this instance.
        if ($result === $this || $result instanceof static || $result === null) {
            foreach ($subsets as $subitem) {
                $subitem->recursively($callback);
            }

            return $this;
        }

        // Collection mode: gather all results into a flat array.
        $collection = [$this->name() => $result];

        foreach ($subsets as $subitem) {
            $inner = $subitem->recursively($callback);

            // Merge nested results (they'll always be arrays in collection mode).
            if (is_array($inner)) {
                $collection = array_merge($collection, $inner);
            }
        }

        return $collection;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function parent(): DataArray|null
    {
        return $this->parent;
    }

    /**
     * @deprecated Hooks are still under development and shouldn't be used at the moment.
     *             The idea, is to be able to add a hook to your DataArray for several occasions
     *             like when an item gets unset, set, updated or requested.
     */
    public function hook(callable $action, Hook|array $on): static
    {
        $this->hooks[$on->value][] = $action;

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
        return $this->newTypeInstance(ArrayIterator::class, ['array' => $this->items]);
    }
}
