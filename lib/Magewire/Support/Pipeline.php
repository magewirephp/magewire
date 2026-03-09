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
 *
 * Allows building a chain of callbacks (pipes) that process data sequentially,
 * with support for middleware, one-time execution and event handlers for error handling and cleanup.
 */
class Pipeline
{
    use WithFactory;

    /** @var array<string, callable> $pipes */
    protected array $pipes = [];
    /** @var Middleware|null $middleware */
    protected Middleware|null $middleware = null;
    /** @var array<string, array<string, callable> $handlers */
    private array $handlers = [];

    public function __construct(
        protected LoggerInterface $logger
    ) {
        
    }

    /**
     * @throws Throwable
     */
    public function run(mixed $throughput): mixed
    {
        return $this->processPipes($throughput);
    }

    /**
     * Register a pipe in the pipeline.
     *
     * Pipes are callbacks that receive the current throughput and a $next callable.
     * They must call $next($throughput) to continue the pipeline chain.
     *
     * @example $pipeline->pipe(fn ($throughput, callable $next) => $next($throughput));
     */
    public function pipe(callable $callback): static
    {
        $this->pipes[] = $callback;

        return $this;
    }

    /**
     * Pipeline middleware entrypoint.
     */
    public function middleware(): Middleware
    {
        return $this->middleware ??= $this->newInstance([], Middleware::class);
    }

    /**
     * Register a handler to execute when an exception occurs during pipeline execution.
     *
     * Catch handlers receive the exception and logger as arguments.
     * Multiple catch handlers can be registered and will all be executed.
     *
     * @example $pipeline->onCatch(fn($e, $logger) => $logger->error($e->getMessage()));
     */
    public function onCatch(callable $handler, string|null $alias = null): static
    {
        return $this->registerHandler('catch', $handler, $alias);
    }

    /**
     * Register a handler to execute after pipeline completion (success or failure).
     *
     * Finally handlers receive the throughput as an argument and are always executed,
     * similar to a try-finally block.
     *
     * @example $pipeline->onFinally(fn($data) => cleanup($data));
     */
    public function onFinally(callable $handler, string|null $alias = null): static
    {
        return $this->registerHandler('finally', $handler, $alias);
    }

    /**
     * Register a handler to execute after pipeline completion (success only).
     *
     * Success handlers receive the throughput as an argument and are always executed
     *
     * @example $pipeline->onFinally(fn($data) => cleanup($data));
     */
    public function onSuccess(callable $handler, string|null $alias = null): static
    {
        return $this->registerHandler('success', $handler, $alias);
    }

    /**
     * Get the number of currently registered pipes.
     */
    public function count(): int
    {
        return count($this->pipes);
    }

    /**
     * Process all pipes in the pipeline with the given throughput.
     *
     * Couples all pipes into a single callable chain, applies middleware if configured,
     * handles exceptions with catch handlers, and ensures finally handlers are executed.
     * Performs cleanup after successful execution to remove one-time pipes.
     */
    protected function processPipes(mixed $throughput): mixed
    {
        try {
            $pipeline = $this->couple($this->pipes);

            $throughput = $this->middleware
                ? $this->middleware()->run($throughput, $pipeline)
                : $pipeline($throughput);

            $this->processHandler('success', $throughput);
        } catch (Throwable $exception) {
            $this->processHandler('catch', $exception, $this->logger);
        } finally {
            $this->processHandler('finally', $throughput);
        }

        return $throughput;
    }

    /**
     * Execute all registered handlers for a specific event type.
     *
     * Automatically logs critical exceptions when processing 'catch' handlers.
     * If a handler itself throws an exception, it's logged but doesn't interrupt other handlers.
     */
    protected function processHandler(string $handler, mixed ...$args): static
    {
        $handlers = $this->handlers[$handler] ?? [];

        // Re-throw exception if no catch handlers are registered to process it.
        if (empty($handlers) && $handler === 'catch' && ($args[0] ?? null) instanceof Throwable) {
            throw $args[0];
        }

        $class = basename(str_replace('\\', '/', __CLASS__));

        foreach ($handlers as $callback) {
            try {
                $callback(...$args);
            } catch (Throwable $exception) {
                $this->logger->error($class . ' exception: ' . $exception->getMessage(), [
                    'handler' => $handler,
                    'exception' => $exception,
                ]);
            }
        }

        return $this;
    }

    /**
     * Register a handler for a specific event type.
     *
     * Prevents duplicate registration of handlers with the same alias unless
     * the force flag is set to true.
     */
    protected function registerHandler(
        string $event,
        callable $handler,
        string|null $alias = null
    ): static
    {
        $this->handlers[$event][$alias ?? Random::alphabetical()] = $handler;

        return $this;
    }

    /**
     * Couple multiple pipes into a single callable chain.
     *
     * Uses array_reduce to build a nested chain of callables where each pipe
     * wraps the next, creating a middleware-style execution pattern.
     */
    protected function couple(array $pipes): callable
    {
        $core = static fn (mixed $throughput): mixed => $throughput;

        foreach (array_reverse($pipes) as $pipe) {
            $core = static function (mixed $throughput) use ($pipe, $core): mixed {
                return $pipe($throughput, $core);
            };
        }

        return $core;
    }
}
