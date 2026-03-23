<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Exception;
use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\DataCollectionFactory;
use Magewirephp\Magewire\Support\Random;

class ServiceTypeBooter
{
    private bool $setup = false;
    private bool $booted = false;

    /** @var array<string, bool> */
    private array $processed = [];

    private DataCollection|null $items = null;

    public function __construct(
        private readonly DataCollectionFactory $dataArrayFactory
    ) {
    }

    public function setup(array $items): static
    {
        if ($this->setup) {
            return $this;
        }

        foreach ($items as $item) {
            if (!($item['boot_mode'] instanceof ServiceTypeItemBootMode)) {
                $item['boot_mode'] = ServiceTypeItemBootMode::try($item['boot_mode'] ?? null);
            }

            $key = $item['name'] ?? Random::string();
            $this->items()->set($key, $item);
            $this->processed[$key] = false;
        }

        $this->setup = true;
        return $this;
    }

    /**
     * Returns true when all items at or above the given mode have been booted.
     * When no mode is given, checks all items and caches the global booted state.
     */
    public function booted(ServiceTypeItemBootMode|null $mode = null): bool
    {
        if ($this->booted) {
            return true;
        }

        foreach ($this->items()->all() as $key => $item) {
            if ($mode !== null && $item['boot_mode']->isLowerThan($mode)) {
                continue;
            }

            if (($this->processed[$key] ?? false) === false) {
                return false;
            }
        }

        if ($mode === null) {
            $this->booted = true;
        }

        return true;
    }

    public function boot(ServiceTypeItemBootMode|null $mode, callable $callback): static
    {
        if ($this->booted) {
            return $this;
        }

        foreach ($this->items()->all() as $key => $item) {
            // Skip items that were already booted.
            if ($this->processed[$key] ?? false) {
                continue;
            }

            // Skip items with a lower priority than the requested mode.
            if ($mode !== null && $item['boot_mode']->isLowerThan($mode)) {
                continue;
            }

            try {
                $callback($item['type'], $item);
                $this->processed[$key] = true;
            } catch (Exception $exception) {
                // TBD
            }
        }

        // Cache the global booted flag if all items are now done.
        $this->booted();

        return $this;
    }

    private function items(): DataCollection
    {
        return $this->items ??= $this->dataArrayFactory->create();
    }
}
