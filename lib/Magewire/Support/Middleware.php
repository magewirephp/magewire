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

class Middleware extends Pipeline
{
    /** @var array<string, Pipeline> $bypasses */
    protected array $groups = [];
    /** @var array<string, int> $positions */
    protected array $positions = [];

    /**
     * @throws Throwable
     */
    public function run(mixed $throughput, callable $next = null): mixed
    {
        if ($this->count() === 0) {
            return $next ? $next($throughput) : $throughput;
        }

        $middleware = $this->couple($this->pipes, $next);

        try {
            $throughput = $middleware($throughput);
        } catch (Throwable $exception) {
            $this->processHandler('catch', $exception, $throughput);
        } finally {
            $this->processHandler('finally', $throughput);
        }

        return $throughput;
    }

    public function group(string $name, $position = 500): Pipeline
    {
        if (count($this->groups) === 0) {
            $this->pipe(function (mixed $throughput, callable $next) {
                $groups = $this->groups;

                uksort($groups, fn ($a, $b) =>
                    ($this->positions[$a] ?? 500) <=> ($this->positions[$b] ?? 500)
                );

                foreach ($groups as $name => $group) {
                    $throughput = $group->run($throughput);
                }

                return $next($throughput);
            });
        }

        // A position can only be set once, and cannot be altered.
        $this->positions[$name] ??= $position;

        return $this->groups[$name] ??= $this->newTypeInstance(Pipeline::class);
    }

    /**
     * @param array<mixed, callable> $pipes
     */
    private function couple(array $pipes, callable $action): callable
    {
        return array_reduce(
            array_reverse($pipes),
            fn ($inner, $pipe) => fn ($throughput) => $pipe($throughput, $inner),
            $action
        );
    }
}
