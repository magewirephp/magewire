<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * A flexible pipeline implementation for chaining operations.
 */
class Pipeline
{
    use WithFactory;

    /** @var array<int, callable> $pipes */
    protected array $pipes = [];
    /** @var Middleware|null $middleware */
    protected Middleware|null $middleware = null;
    /** @var array<string, array<int, callable>> */
    protected array $stashes = [];
    /** @var array<string, array<int, callable> $handlers */
    protected array $handlers = [];
    /** @var array<int, string> $persists */
    protected array $persists = [];

    public function __construct(
        private LoggerInterface $logger
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function run(mixed $throughput): mixed
    {
        return $this->processPipes($throughput);
    }

    /**
     * Register a (aliased) pipeline section.
     *
     * @example $pipeline->pipe(fn ($throughput, callable $next) => $next($throughput));
     */
    public function pipe(callable $callback, string|null $alias = null, bool $force = false): static
    {
        $alias ??= Random::alphabetical(20);

        // Validate conditions for when not to add the given pipe.
        if (in_array($alias, $this->persists) || (! $force && isset($this->pipes[$alias]))) {
            return $this;
        }

        $this->pipes[$alias] = $callback;
        return $this;
    }

    /**
     * Persists the given pipe.
     */
    public function persist(string $alias): static
    {
        $this->persists[] = $alias;
        return $this;
    }

    public function middleware(): Middleware
    {
        return $this->middleware ??= $this->newInstance([], Middleware::class);
    }

    /**
     * Remove all pipes.
     */
    public function clear(): static
    {
        $this->pipes = [];

        return $this;
    }

    /**
     * Returns the number of pipes.
     */
    public function count(): int
    {
        return count($this->pipes);
    }

    /**
     * Temporarily stash current pipes.
     */
    public function stash(string $name): static
    {
        $this->stashes[$name] = $this->pipes;

        return $this->clear();
    }

    /**
     * Unstash a stashed set of pipes at the end of the current pipeline.
     */
    public function unstash(string $name): static
    {
        if (isset($this->stashes[$name])) {
            foreach ($this->stashes[$name] as $callback) {
                $this->pipe($callback);
            }

            unset($this->stashes[$name]);
        }

        return $this;
    }

    public function onCatch(callable $handler): static
    {
        $this->handlers['catch'][] = $handler;
        return $this;
    }

    public function onFinally(callable $handler): static
    {
        $this->handlers['finally'][] = $handler;
        return $this;
    }

    protected function processHandler(string $handler, mixed ...$args): static
    {
        // Always log any Pipeline exception no matter what.
        if ($handler === 'catch' && $args[0] ?? null instanceof Throwable) {
            $this->logger->critical('Pipeline', ['exception' => $args[0]]);
        }

        foreach ($this->handlers[$handler] ?? [] as $callback) {
            try {
                $callback(...$args);
            } catch (Throwable $exception) {
                $this->logger->error('Pipeline handler failed: ' . $exception->getMessage(), [
                    'handler' => $handler,
                    'exception' => $exception,
                ]);
            }
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    protected function processPipes(mixed $throughput): mixed
    {
        if ($this->count() === 0) {
            return $throughput;
        }

        // Build the final pipeline.
        $pipeline = $this->couple($this->pipes);

        try {
            $throughput = $this->middleware
                ? $this->middleware()->run($throughput, fn ($throughput) => $pipeline($throughput))
                : $pipeline($throughput);
        } catch (Throwable $exception) {
            $this->processHandler('catch', $exception, $this->logger);
        } finally {
            $this->processHandler('finally', $throughput);
        }

        return $throughput;
    }

    /**
     * @param array<mixed, callable> $pipes
     */
    private function couple(array $pipes): callable
    {
        // Acts as the final "next" callable in the pipeline.
        $passthrough = fn ($throughput) => $throughput;
        // Wraps each pipe around the existing pipeline.
        $decorator = fn ($next, $pipe) => fn ($throughput) => $pipe($throughput, $next);
        // Construct the final pipeline.
        return array_reduce(array_reverse($pipes), $decorator, $passthrough);
    }
}
