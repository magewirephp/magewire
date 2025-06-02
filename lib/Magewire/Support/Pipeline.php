<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Exception;
use Magewirephp\Magewire\Support\Concerns\WithFactory;
use Psr\Log\LoggerInterface;

class Pipeline
{
    use WithFactory;

    /** @var array<int, callable> $pipes */
    protected array $pipes = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        //
    }

    /**
     * Extend the current pipeline with an additional callback.
     */
    public function pipe(callable $callback): static
    {
        $this->pipes[] = $callback;

        return $this;
    }

    /**
     * Try to run the pipeline using a mixed type throughput.
     */
    public function run($throughput)
    {
        // Wraps each pipe around the existing pipeline.
        $piper = fn ($next, $pipe) => fn ($throughput) => $pipe($throughput, $next);
        // Acts as the final "next" callable in the pipeline
        $actor = fn ($throughput) => $throughput;
        // Construct the final pipeline.
        $pipeline = array_reduce(array_reverse($this->pipes), $piper, $actor);

        try {
            return $pipeline($throughput);
        } catch (Exception $exception) {
            $this->logger->critical('Pipeline failure', ['exception' => $exception]);
        }

        return $throughput;
    }
}
