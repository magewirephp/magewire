<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magento\Framework\App\ObjectManager;

/**
 * @deprecated work in progress.
 */
class DataScope
{
    /** @var array<int, string> $latest */
    private array $latest = [];

    public function __construct(
        private readonly DataScope|null $root = null,
        protected array $data = [],
    ) {
        //
    }

    /**
     * Sets a value at the given path, supporting aliasing and array stacking for paths ending in 's'.
     * Automatically tracks the latest modified path, including numeric keys or aliases.
     * Ensures the target structure exists before assigning the value.
     */
    public function set(string $path, mixed $value, string|null $alias = null): static
    {
        $keys = explode('.', $path);
        $target = array_pop($keys);

        $current = &$this->data($keys, true);

        if (! is_array($current)) {
            $current = [];
        }

        if (str_ends_with($path, 's')) {
            if (! isset($current[$target]) || ! is_array($current[$target])) {
                $current[$target] = [];
            }

            if ($alias) {
                $current[$target][$alias] = $value;
                $this->latest[] = $path . '.' . $alias;
            } else {
                $current[$target][] = $value;
                $this->latest[] = $path . '.' . array_key_last($current[$target]);
            }

            return $this;
        }

        $current[$target] = $value;
        $this->latest[] = $path;

        return $this;
    }

    /**
     * Pushes a value onto the specified section, appending it to an array if the section ends with 's'.
     * Supports aliasing for array elements and ensures the target structure exists before adding the value.
     * Automatically tracks the latest modified path.
     */
    public function push(string $section, mixed $value, string|null $alias = null, string|null $path = null): static
    {
        $section = rtrim($section, 's') . 's';

        return $this->set($path ? $path . '.' . $section : $section, $value, $alias);
    }

    /**
     * Groups a value under the specified path by appending it to an array within the given group.
     * Ensures the target structure exists, initializing it as an array if necessary, before adding the value.
     * Automatically tracks the latest modified path.
     */
    public function group(string $group, string $path, $value): static
    {
        $path = $path . '.' . $group;

        $keys = explode('.', $path);
        $current = &$this->data($keys, true);

        if (! is_array($current)) {
            $current = [];
        }

        $current[] = $value;
        $this->latest[] = $path . '.' . array_key_last($current);

        return $this;
    }

    /**
     * Retrieves a value from the given path, with optional alias support and a default fallback.
     * Uses the data method to navigate nested structures while handling array keys gracefully.
     * Returns the value if found, or the default if the path or alias does not exist.
     */
    public function get(string $path, string|null $alias = null, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        $target = array_pop($keys);

        $current = $this->data($keys);
        $target = $alias ?? $target;

        if (is_array($current) && array_key_exists($target, $current)) {
            return $current[$target];
        }

        return $default;
    }

    /**
     * Checks if a value exists at the given path, optionally using an alias.
     * Returns true if the value exists, false otherwise.
     */
    public function isset(string $path, string|null $alias = null): bool
    {
        return $this->get($path, $alias) !== null;
    }

    /**
     * Removes the value at the given path, supporting aliases.
     * Uses the data method to navigate and unset the key if it exists.
     */
    public function unset(string $path, string|null $alias = null): static
    {
        $keys = explode('.', $path);
        $target = array_pop($keys);

        $current = &$this->data($keys);
        $target = $alias ?? $target;

        if (is_array($current) && array_key_exists($target, $current)) {
            unset($current[$target]);
        }

        return $this;
    }

    /**
     * Replaces the value at the given path with the specified value, supporting aliasing.
     * Uses the data method to navigate nested structures and modifies the value if the key exists.
     * Does nothing if the path or alias does not exist.
     */
    public function replace(string $path, mixed $value, string|null $alias = null): static
    {
        $keys = explode('.', $path);
        $target = array_pop($keys);

        $current = &$this->data($keys);
        $target = $alias ?? $target;

        if (is_array($current) && array_key_exists($target, $current)) {
            $current[$target] = $value;
        }

        return $this;
    }

    /**
     * Returns all data, optionally filtered using a callback.
     * Applies the provided filter to the data if given; otherwise, returns the full dataset.
     */
    public function fetch(callable|null $filter = null): array
    {
        return $filter ? array_filter($this->data, $filter) : $this->data;
    }

    public function merge(array $data): static
    {
        return $this;
    }

    /**
     * Retrieves the most recently modified or added item, or the full dataset if none are tracked.
     */
    public function latest(): mixed
    {
        $latest = array_pop($this->latest);

        return $latest ? $this->get($latest) : $this->data;
    }

    /**
     * Creates a new data scope branching from the root.
     *
     * Initializes a new data scope at the specified path with a given alias.
     * The newly created scope retains access to the root data, allowing for hierarchical data management.
     * If a scope already exists at the given path and alias, it is returned directly.
     */
    public function scope(string $path, string $alias, bool $isolate = false, string $scope = DataScope::class): DataScope
    {
        $branch = $this->get($path, $alias);

        if ($branch) {
            return $branch;
        }

        $branch = ObjectManager::getInstance()->create($scope, [
            'root' => $isolate ? $this : $this->root()
        ]);

        return $this->set($path, $branch, $alias)->latest();
    }

    /**
     * Returns the root data scope instance.
     * If no root is set, returns the current instance as the root.
     */
    public function root(): DataScope
    {
        return $this->root ?? $this;
    }

    /**
     * Traverses the data structure by following the given keys, optionally creating missing keys.
     * Returns a reference to the target value if found, or null if the path is invalid and no construction is allowed.
     * Supports array key navigation with an option to auto-construct missing arrays.
     */
    private function &data(array $keys, bool $construct = false): mixed
    {
        $current = &$this->data;

        foreach ($keys as $key) {
            if (! isset($current[$key])) {
                if ($construct) {
                    $current[$key] = [];
                }
            }

            if (! is_array($current[$key])) {
                if ($construct) {
                    $current[$key] = [];
                }
            }

            $current = &$current[$key];
        }

        return $current;
    }
}
