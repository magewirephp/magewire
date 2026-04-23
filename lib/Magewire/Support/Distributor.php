<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

/**
 * Lazy instance distributor for named object instances.
 *
 * The distributor acts as a factory that creates and caches instances of a specific type
 * on-demand through magic method calls. Each method name becomes a unique key for
 * storing and retrieving instances.
 *
 * Use this when you need multiple named instances of the same type that should be
 * lazily instantiated and reused throughout the application lifecycle.
 *
 * @template T of object
 */
abstract class Distributor
{
    /** @var array<string, T> */
    protected array $instances = [];

    /**
     * @param class-string<T> $type
     * @param array<string, class-string<T>> $mapping
     */
    public function __construct(
        protected string $type,
        protected array $mapping = []
    ) {
    }

    /**
     * @return object<T>
     */
    public function __call(string $name, array $arguments = [])
    {
        return $this->instances[$name] ??= $this->create($name, $arguments);
    }

    /**
     * Resolve the appropriate type for a given name.
     *
     * @param string $name The instance identifier
     * @return class-string<T> The resolved class name
     */
    protected function resolve(string $name): string
    {
        $map = $this->mapping[$name] ?? null;

        if ($map !== null && is_a($map, $this->type, true)) {
            return $map;
        }

        return $this->type;
    }

    protected function create(string|null $type = null, array $arguments = []): object
    {
        if ($type === null) {
            return Factory::create($this->type, $arguments);
        }

        return Factory::create($this->resolve($type), $arguments);
    }
}
