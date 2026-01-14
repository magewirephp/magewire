<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magewirephp\Magewire\Support\Factory;

class SlotsRegistry
{
    /** @var array<int, array<string, Slot>> Stack of slot collections */
    private array $slots = [];

    /**
     * Start tracking slots for a new component (push new slot collection onto stack)
     */
    public function track(): void
    {
        $this->slots[] = [];
    }

    /**
     * Stop tracking slots for current component (pop from stack)
     *
     * @return array<string, Slot> The slots for the component that just finished
     */
    public function untrack(): array
    {
        return array_pop($this->slots) ?? [];
    }

    private function register(string $name): Slot
    {
        $slot = Factory::create(Slot::class, ['name' => $name]);

        if (! empty($this->slots)) {
            $this->slots[array_key_last($this->slots)][$slot->name()] = $slot;
        }

        return $slot;
    }

    /**
     * Get all slots for the current component
     *
     * @return array<string, Slot>
     */
    public function getCurrentSlots(): array
    {
        return end($this->slots) ?: [];
    }

    /**
     * Get a specific slot from the current component
     */
    public function get(string $name): ?Slot
    {
        $current = $this->getCurrentSlots();

        return $current[$name] ?? null;
    }

    /**
     * Check if a slot exists in the current component
     */
    public function has(string $name): bool
    {
        return isset($this->getCurrentSlots()[$name]);
    }

    public function update(string $name, string $content): static
    {
        $slot = $this->get($name) ?? $this->register($name);
        $slot->update($content);

        return $this;
    }

    public function print(string $name): string
    {
        if ($this->has($name)) {
            return $this->get($name)->__toString();
        }

        return '';
    }
}
