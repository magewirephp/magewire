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
 * Middleware layer for pipeline processing.
 *
 * Extends Pipeline to provide middleware-specific functionality with support for
 * grouping middleware with configurable execution order.
 */
class Middleware extends Pipeline
{
    /** @var array<string, Pipeline> */
    protected array $groups = [];
    /** @var array<string, int> */
    protected array $positions = [];

    /**
     * Main entry point when this middleware wraps the core pipeline.
     *
     * Receives $throughput and the core pipeline closure ($action).
     */
    public function run(mixed $throughput, callable|null $action = null): mixed
    {
        $throughput = $this->runGroups($throughput);
        $originalPipes = $this->pipes;

        if ($action !== null) {
            $this->pipes[] = $action;
        }

        try {
            return parent::run($throughput);
        } finally {
            $this->pipes = $originalPipes;
        }
    }

    /**
     * Execute all groups in position order (ascending = lower first).
     * @throws \Throwable
     */
    protected function runGroups(mixed $throughput): mixed
    {
        if (empty($this->groups)) {
            return $throughput;
        }

        $sortedGroups = $this->groups;

        uasort($sortedGroups, function ($a, $b) {
            $posA = $this->positions[array_search($a, $this->groups, true)] ?? 500;
            $posB = $this->positions[array_search($b, $this->groups, true)] ?? 500;

            return $posA <=> $posB;
        });

        foreach ($sortedGroups as $group) {
            $throughput = $group->run($throughput);
        }

        return $throughput;
    }

    /**
     * Create or retrieve a middleware group with execution position.
     */
    public function group(string $name, int $position = 500): Pipeline
    {
        $this->positions[$name] ??= $position;

        if (!isset($this->groups[$name])) {
            $this->groups[$name] = $this->newTypeInstance(Pipeline::class);
        }

        return $this->groups[$name];
    }

    protected function couple(array $pipes): callable
    {
        // Your original version – first registered = innermost
        $decorator = fn ($next, $pipe) => fn ($throughput) => $pipe($throughput, $next);
        return array_reduce($pipes, $decorator, fn ($throughput) => $throughput);
    }
}
