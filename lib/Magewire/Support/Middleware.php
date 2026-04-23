<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Throwable;

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

    public function run(mixed $throughput, callable|null $action = null): mixed
    {
        $origin = $this->pipes;
        $this->pipes[] = $action ?? static fn (mixed $t) => $t;

        try {
            return parent::run($this->processGroups($throughput));
        } finally {
            $this->pipes = $origin;
        }
    }

    /**
     * Create or retrieve a middleware group with execution position.
     */
    public function group(string $name, int $position = 500): Pipeline
    {
        $this->positions[$name] ??= $position;

        if (! isset($this->groups[$name])) {
            $this->groups[$name] = $this->newTypeInstance(Pipeline::class);
        }

        return $this->groups[$name];
    }

    /**
     * Execute all groups in position order (ascending = lower first).
     *
     * @throws Throwable
     */
    protected function processGroups(mixed $throughput): mixed
    {
        if (empty($this->groups)) {
            return $throughput;
        }

        $groups = $this->groups;

        uasort($groups, function ($a, $b) {
            $posA = $this->positions[array_search($a, $this->groups, true)] ?? 500;
            $posB = $this->positions[array_search($b, $this->groups, true)] ?? 500;

            return $posA <=> $posB;
        });

        foreach ($groups as $group) {
            $throughput = $group->run($throughput);
        }

        return $throughput;
    }
}
