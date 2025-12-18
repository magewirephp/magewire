<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use BadMethodCallException;
use Exception;
use Psr\Log\LoggerInterface;

class MagewireServiceProvider
{
    private State|null $state = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly StateFactory $stateFactory,
        private readonly Containers $containers,
        private readonly Mechanisms $mechanisms,
        private readonly Features $features,
        private readonly bool $boot = false
    ) {
        //
    }

    public function setup(Mode $mode = Mode::PRECEDING): void
    {
        if ($this->boot) {
            $this->boot($mode);
        }
    }

    public function boot(Mode $mode): void
    {
        if ($this->state()->isActive()) {
            return;
        }

        // Set boot mode, before we do anything else.
        $this->state()->mode($mode);

        // Boot service types manually.
        $this->containers->boot();
        $this->mechanisms->boot();
        $this->features->boot();

        // Booted up, ready to start.
        $this->state()->start();
    }

    public function state(): State
    {
        return $this->state ??= $this->stateFactory->create();
    }

    public function __call($name, $arguments)
    {
        $matches = preg_match_all('/[A-Z][a-z]*/', $name, $matches) ? $matches[0] : [];

        $operation = $this->getServiceType($matches[count($matches) - 1]);
        $operation = $operation ?? $this->getServiceType($matches[count($matches) - 2]);

        /*
         * Enables the possibility to get either a mechanism- or a feature operation type or its
         * belonging facade if it has any attached.
         *
         * @todo This lookup can be heavily optimized caching those that are found
         *       by there $name and when exist as key in a global class array,
         *       return that type instead of looking it up once again.
         */
        if ($operation) {
            $item = strtolower($matches[count($matches) - 1]);

            $argument = strtolower(implode('_', array_slice($matches, 0, count($matches) - 1)));
            $argument = trim(preg_replace('/(?:container|mechanism|feature)\s*$/', '', $argument), '_');

            try {
                return match ($item) {
                    'container', 'feature', 'mechanism' => $operation->item($argument),
                    'facade' => $operation->facade($argument)
                };
            } catch (Exception $exception) {
                $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
            }
        }

        throw new BadMethodCallException();
    }

    private function getServiceType(string $operation): ?ServiceType
    {
        $operation = strtolower(! str_ends_with($operation, 's') ? $operation . 's' : $operation);

        if (property_exists(self::class, $operation)) {
            return $this->{$operation};
        }

        return null;
    }
}
