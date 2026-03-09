<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Countable;
use IteratorAggregate;
use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Magewirephp\Magewire\Support\DataCollection\Filter;
use Magewirephp\Magewire\Support\DataCollection\Hook;
use Magewirephp\Magewire\Support\DataCollection\TypeFilter;

abstract class DataCollection implements Countable, IteratorAggregate
{
    use WithFactory;

    /** @var array<string, DataCollection> */
    private array $subitems = [];
    /** @var array<Hook, array<int, callable>> */
    private array $hooks = [];

    /**
     * @param array<string|int, mixed> $items
     */
    public function __construct(
        private readonly Filter $filter,
        private array $items = [],
        private readonly int $level = 0,
        private readonly string $name = 'root',
        private readonly DataCollection|null $parent = null
    ) {
    }

    public function map(array $map): static
    {
        foreach ($map as $from => $to) {
            if (! ( ( is_string($from) || is_int($from) ) && is_string($to) )) {
                continue;
            }

            $this->rename($from, $to);
        }

        return $this;
    }

    public function rename(string|int $from, string|int $to): static
    {
        return $this->copy($from, $to, true);
    }

    public function each(callable $callback, callable|TypeFilter $filter = TypeFilter::ALL): static
    {
        $items = $this->filter()
            ->with($filter)
            ->return()
            ->all();

        foreach ($items as $key => $value) {
            $callback($this, $value, $key);
        }

        return $this;
    }

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
            if (! $this->isset($key)) {
                continue;
            }

            unset($this->items[$key]);
        }

        return $this;
    }

    public function set(string|int $name, $value): static
    {
        if ($this->isset($name)) {
            return $this;
        }

        $this->items[$name] = $value;
        return $this;
    }

    public function push($value): static
    {
        $this->items[] = $value;
        return $this;
    }

    public function subset(string|int|null $name = null, string|null $type = null, array $arguments = []): DataCollection
    {
        $level = $name ? $this->level + 1 : 0;
        $name = Str::snake($name);

        if (isset($this->subitems[$name])) {
            return $this->subitems[$name];
        }

        $arguments = array_merge($arguments, [
            'parent' => $this,
            'level' => $level,
            'name' => $name
        ]);

        if ($name === null) {
            return $this->newTypeInstance($type ?? static::class, $arguments);
        }

        return $this->subitems[$name] ??= $this->newTypeInstance($type ?? static::class, $arguments);
    }

    public function subsets(): array
    {
        return $this->subitems;
    }

    public function put(string|int $name, $value): static
    {
        if ($this->isset($name)) {
            $this->items[$name] = $value;
        }

        return $this;
    }

    public function merge(array $items): static
    {
        foreach ($items as $key => $value) {
            $this->isset($key) ? $this->put($key, $value) : $this->set($key, $value);
        }

        return $this;
    }

    public function fill(array $items, bool $arrayAsSubset = false): static
    {
        foreach ($items as $key => $value) {
            $target = $this;

            if (is_array($value) && $arrayAsSubset) {
                $target = $this->subset($key);
            }

            $target->isset($key) ? $target->put($key, $value) : $target->set($key, $value);
        }

        return $this;
    }

    public function isset(string|int $name): bool
    {
        return isset($this->items[$name]);
    }

    public function has(string|int $name): bool
    {
        return $this->isset($name);
    }

    public function all(): array
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            if ($value instanceof DataCollection) {
                $value = $value->all();
            }

            $result[$key] = $value;
        }

        return $result;
    }

    public function raw(): array
    {
        return $this->items;
    }

    /**
     * Encodes the collection to a JSON string.
     * When a filter is provided, only items passing both the JSON_ENCODABLE
     * filter and the supplied filter are included in the output.
     *
     * @todo Implement lazy JSON caching when PHP 8.4+ property hooks are available.
     *        Use a cached $json property with a set hook that invalidates the cache whenever
     *        $items is modified. This would allow returning pre-encoded JSON if items haven't
     *        changed, eliminating redundant encoding operations. The cache would only contain
     *        JSON-encodable items to ensure validity.
     *
     * @throws \JsonException if encoding fails.
     */
    public function json(callable|TypeFilter|null $filter = null): string|false
    {
        $items = $this;

        if ($filter) {
            $items = $this->subset()
                ->fill(
                    $this->filter()
                        ->with(TypeFilter::JSON_ENCODABLE)
                        ->return()
                        ->all()
                )
                ->filter()
                ->with($filter)
                ->return();
        }

        return json_encode($items->all());
    }

    public function filter(): Filter
    {
        return $this->filter->using($this);
    }

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

    public function values(): array
    {
        return array_values($this->items);
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function get(string|int $name, $default = null, bool $set = false): mixed
    {
        return $this->items[$name] ?? ( $set ? $this->default($name, $default)->get($name) : $default );
    }

    public function reset(): static
    {
        $this->items = [];

        return $this;
    }

    public function destroy(): static
    {
        $this->reset();
        $this->subitems = [];

        return $this;
    }

    public function default(string|int $key, $value): static
    {
        if ($this->isset($key)) {
            return $this;
        }

        return $this->set($key, $value);
    }

    public function defaults(array $defaults): static
    {
        foreach ($defaults as $key => $value) {
            if (! ( is_string($key) || is_int($key) )) {
                continue;
            }

            $this->default($key, $value);
        }

        return $this;
    }

    public function clear(callable|TypeFilter $filter = TypeFilter::NONE): static
    {
        $this->items = $this->filter()
            ->with($filter)
            ->return()
            ->all();

        return $this;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function snapshot(): static
    {
        return $this->newInstance(['items' => $this->all()]);
    }

    public function walk(callable $callback): static
    {
        $result = $callback($this);
        $subsets = $this->subsets();

        // Callback returned nothing or this instance.
        if ($result === $this || $result instanceof static || $result === null) {
            foreach ($subsets as $subitem) {
                $subitem->walk($callback);
            }
        }

        return $this;
    }

    public function collect(callable $callback): array
    {
        $result = $callback($this);
        $subsets = $this->subsets();

        // Gather all results into a flat array.
        $collection = [$this->name() => $result];

        foreach ($subsets as $subitem) {
            $collection = array_merge($collection, $subitem->collect($callback));
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

    public function parent(): static|null
    {
        return $this->parent;
    }

    /**
     * Registers a callable to be triggered when the specified hook event occurs.
     *
     * @deprecated Hooks are under development and not yet functional (undecided if needed).
     */
    public function hook(callable $action, Hook|string $on): static
    {
        $hook = is_string($on) ? $on : $on->value;
        $this->hooks[$hook][] = $action;

        return $this;
    }

    /**
     * Dispatches a hook by invoking all registered actions for the given hook,
     * passing the current instance followed by any additional arguments.
     *
     * @deprecated Hooks are under development and not yet functional (undecided if needed).
     */
    protected function dispatch(Hook|string $hook, mixed ...$args): static
    {
        $hook = is_string($hook) ? $hook : $hook->value;

        foreach ($this->hooks[$hook] ?? [] as $action) {
            $action($this, ...$args);
        }

        return $this;
    }
}
