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
use Magewirephp\Magewire\Enums\RequestMode;
use Magewirephp\Magewire\Enums\RuntimeState;
use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;
use Psr\Log\LoggerInterface;
use RuntimeException;

class MagewireServiceProvider
{
    private Runtime|null $runtime = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RuntimeFactory $runtimeFactory,
        private readonly Containers $containers,
        private readonly Mechanisms $mechanisms,
        private readonly Features $features
    ) {
    }

    public function setup(): void
    {
        if ($this->runtime()->state()->isMinimally(RuntimeState::SETUP)) {
            return;
        }

        try {
            // Containers are always fully booted during setup.
            $this->containers->boot();

            // Before setup, but after containers service type booted.
            trigger('magewire:setup', $this->runtime());

            // Boot only persistent and above during setup.
            $this->mechanisms->boot(ServiceTypeItemBootMode::PERSISTENT);
            $this->features->boot(ServiceTypeItemBootMode::PERSISTENT);

            $this->runtime()->state(RuntimeState::SETUP);
        } catch (Exception $exception) {
            $this->runtime()->state(RuntimeState::FAILED);

            throw $exception;
        }
    }

    /**
     * @throws Exception
     */
    public function boot(RequestMode $mode, bool $force = false): void
    {
        if (! $force && $this->runtime()->state()->is(RuntimeState::UNINITIALIZED)) {
            $this->setup();
        }
        if (! $force && $this->runtime()->state()->isMinimally(RuntimeState::BOOTED)) {
            return;
        }

        try {
            // Define runtime boot mode.
            $this->runtime()->mode($mode);
            // Define runtime booting state right before service items boot attempt.
            $this->runtime()->state(RuntimeState::BOOTING);

            // Before boot.
            $finish = trigger('magewire:boot', $this->runtime());

            // Boot all remaining service types not yet booted during setup.
            $boot['containers'] = $this->mechanisms->boot();
            $boot['mechanisms'] = $this->mechanisms->boot();
            $boot['features'] = $this->features->boot();

            if (in_array(false, $boot, true)) {
                throw new RuntimeException('One or more service types were unable to boot completely.');
            }

            // After boot without any exceptions.
            $finish();

            // Define runtime boot state as ready when all succeeded.
            $this->runtime()->state(RuntimeState::BOOTED);
        } catch (Exception $exception) {
            $this->runtime()->state(RuntimeState::FAILED);

            throw $exception;
        }
    }

    public function runtime(): Runtime
    {
        return $this->runtime ??= $this->runtimeFactory->create();
    }

    public function __call($name, $arguments)
    {
        $matches = preg_match_all('/[A-Z][a-z]*/', $name, $matches) ? $matches[0] : [];

        $operation = $this->getServiceType($matches[count($matches) - 1]);
        $operation ??= $this->getServiceType($matches[count($matches) - 2]);

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

    private function getServiceType(string $operation): ServiceType|null
    {
        $operation = strtolower(! str_ends_with($operation, 's') ? $operation . 's' : $operation);

        if (property_exists(self::class, $operation)) {
            return $this->{$operation};
        }

        return null;
    }
}
