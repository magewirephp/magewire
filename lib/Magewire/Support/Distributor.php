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
 * This class acts as a factory that creates and caches instances of a specific type
 * on-demand through magic method calls. Each method name becomes a unique key for
 * storing and retrieving instances.
 *
 * Use this when you need multiple named instances of the same type that should be
 * lazily instantiated and reused throughout the application lifecycle.
 *
 * @template T of object
 */
class Distributor
{
    /** @var array<string, T> */
    private array $instances = [];

    /**
     * @param class-string<T> $type
     */
    public function __construct(
        private string $type
    ) {
        //
    }

    /**
     * @return T
     */
    public function __call(string $name, array $arguments = [])
    {
        return $this->instances[$name] ??= Factory::create($this->type);
    }
}
