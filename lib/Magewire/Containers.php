<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magento\Framework\Exception\NotFoundException;
use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;

class Containers extends ServiceType
{
    /**
     * Determine whether an alias is configured without resolving it.
     */
    public function has(string $name): bool
    {
        return array_key_exists($this->normalizeItemName($name), $this->items);
    }

    /**
     * Return the configured Magento type for an alias without resolving it.
     *
     * @throws NotFoundException
     */
    public function itemType(string $name): string
    {
        $name = $this->normalizeItemName($name);

        if (! array_key_exists($name, $this->items)) {
            throw new NotFoundException(__('Container item "%1" could not be found.', $name));
        }

        $item = $this->items[$name];

        if (is_string($item)) {
            return $item;
        }

        $type = $item['requested_type'] ?? $item['type'] ?? null;

        if (is_string($type)) {
            return $type;
        }
        if (is_object($type)) {
            return $type::class;
        }

        throw new NotFoundException(__('Container item "%1" has no resolvable type.', $name));
    }

    protected function callback(): callable
    {
        return static fn () => true;
    }

    protected function getBootModeFallback(): ServiceTypeItemBootMode
    {
        return ServiceTypeItemBootMode::ALWAYS;
    }

    private function normalizeItemName(string $name): string
    {
        return preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name;
    }
}
