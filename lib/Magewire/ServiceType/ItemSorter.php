<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\ServiceType;

use LogicException;
use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final class ItemSorter
{
    /**
     * @param array<string, array<string, mixed>> $items
     * @return array<string, array<string, mixed>>
     */
    public function sort(array $items): array
    {
        uasort($items, static fn (array $a, array $b): int => (int) ( $a['sort_order'] ?? 0 ) <=> (int) ( $b['sort_order'] ?? 0 ));

        $this->validateBootModes($items);

        $sorted = [];
        $pending = $items;

        while ($pending !== []) {
            $progress = false;

            foreach ($pending as $name => $item) {
                foreach ($this->dependencies($item, $items) as $dependency) {
                    if (! array_key_exists($dependency, $sorted)) {
                        continue 2;
                    }
                }

                $sorted[$name] = $item;
                unset($pending[$name]);
                $progress = true;
            }

            if (! $progress) {
                throw new LogicException(sprintf('Circular service type sequence detected among items: "%s".', implode('", "', array_keys($pending))));
            }
        }

        return $sorted;
    }

    /**
     * @param array<string, array<string, mixed>> $items
     */
    private function validateBootModes(array $items): void
    {
        foreach ($items as $name => $item) {
            $bootMode = $this->bootMode($item);

            if ($bootMode->isLazy()) {
                continue;
            }

            foreach ($this->dependencies($item, $items) as $dependency) {
                if ($this->bootMode($items[$dependency])->isLazy()) {
                    throw new LogicException(sprintf('Service type item "%s" cannot sequence after lazy item "%s". Align their boot_mode values or make "%s" lazy.', $name, $dependency, $name));
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, array<string, mixed>> $items
     * @return list<string>
     */
    private function dependencies(array $item, array $items): array
    {
        $sequence = $item['sequence'] ?? [];

        if (! is_array($sequence)) {
            return [];
        }

        return array_values(array_filter(array_keys($sequence), static fn (string|int $dependency): bool => ( $sequence[$dependency] ?? false ) === true && array_key_exists($dependency, $items)));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function bootMode(array $item): ServiceTypeItemBootMode
    {
        $bootMode = $item['boot_mode'] ?? null;

        return $bootMode instanceof ServiceTypeItemBootMode ? $bootMode : ServiceTypeItemBootMode::try($bootMode);
    }
}
