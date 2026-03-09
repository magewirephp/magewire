<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Enums;

enum RuntimeState: int
{
    /**
     * Magewire has not started the boot process.
     * Initial state before any initialization occurs.
     */
    case UNINITIALIZED = 0;

    /**
     * Magewire is actively executing the boot sequence.
     * Dependencies, services, and components are being loaded.
     */
    case SETUP = 1;

    /**
     * Magewire booted successfully but some components are unavailable.
     * Core functionality works but non-critical features may be disabled.
     */
    case DEGRADED = 2;

    /**
     * Magewire is fully booted and all components are operational.
     * System is healthy and ready to handle requests.
     */
    case READY = 3;

    /**
     * Boot process encountered a critical error and cannot continue.
     * Application is non-functional and requires intervention.
     */
    case FAILED = 4;

    /**
     * Magewire was previously running but has been shut down.
     * Graceful termination after successful boot and operation.
     */
    case STOPPED = 5;

    /**
     * Check if this state matches the given state.
     */
    public function is(RuntimeState $state): bool
    {
        return $this === $state;
    }

    /**
     * Check if this state matches any of the given states.
     */
    public function isAny(array $states): bool
    {
        return in_array($this, $states, strict: true);
    }

    /**
     * Check if this state is above the given state.
     */
    public function isAbove(RuntimeState $state): bool
    {
        return $this->value > $state->value;
    }

    /**
     * Check if this state is below the given state.
     */
    public function isBelow(RuntimeState $state): bool
    {
        return $this->value < $state->value;
    }

    public function isMinimally(RuntimeState $state): bool
    {
        return $this->value >= $state->value;
    }

    public function isMaximally(RuntimeState $state): bool
    {
        return $this->value <= $state->value;
    }
}
