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
use RuntimeException;
use Throwable;

class Pipeline
{
    use WithFactory;

    /** @var array<int, callable> $pipes */
    private array $pipes = [];

    private int $limit = 1;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        //
    }

    /**
     * Extend the current pipeline with an additional callback.
     *
     * @example $pipeline->pipe(fn ($throughput, callable $next) => $next($throughput));
     */
    public function pipe(callable $callback): static
    {
        $this->pipes[] = $callback;

        return $this;
    }

    /**
     * Try to run the pipeline using mixed type throughput.
     *
     * @throws RuntimeException
     */
    public function run(mixed $throughput): mixed
    {
        if ($this->limit === 0) {
            throw new RuntimeException('Pipeline limit reached.');
        }

        $this->limit--;

        // Wraps each pipe around the existing pipeline.
        $decorator = fn ($next, $pipe) => fn ($throughput) => $pipe($throughput, $next);
        // Acts as the final "next" callable in the pipeline
        $passthrough = fn ($throughput) => $throughput;
        // Construct the final pipeline.
        $pipeline = array_reduce(array_reverse($this->pipes), $decorator, $passthrough);

        try {
            return $pipeline($throughput);
        } catch (Throwable $exception) {
            $this->logger->critical('Pipeline failure', ['exception' => $exception]);
        }

        return $throughput;
    }

    public function limit(int $limit): static
    {
        $this->limit !== 0 && $this->limit = $limit;
        return $this;
    }
}
