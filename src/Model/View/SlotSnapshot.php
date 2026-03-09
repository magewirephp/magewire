<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Stringable;

/**
 * Immutable snapshot of slots captured at a specific point in time.
 *
 * A SlotSnapshot provides a read-only view of a collection of slots,
 * allowing retrieval by name through callable invocation. When cast to
 * a string, it returns the content of the default slot.
 *
 * This is useful for passing slot collections between template layers
 * or storing slot states for later rendering.
 *
 * @deprecated Work in progress, do not use in production.
 */
readonly class SlotSnapshot implements Stringable
{
    /**
     * @param array<string, Slot> $slots
     */
    public function __construct(
        private array $slots = []
    ) {
    }

    /**
     * Retrieve a slot by name.
     *
     * Allows the snapshot to be called as a function to access slots.
     * Returns null if the requested slot doesn't exist.
     */
    public function __invoke(string $name = 'default'): Slot|string|null
    {
        return $this->slots[$name] ?? null;
    }

    /**
     * Convert the snapshot to its string representation.
     *
     * Returns the content of the default slot when the snapshot is used
     * in a string context (e.g., echo, concatenation).
     */
    public function __toString()
    {
        return $this->slots['default']->__toString();
    }
}
