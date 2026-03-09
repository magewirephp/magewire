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

class ServiceTypeItemsBooter
{
    private bool $setup = false;
    private bool $booted = false;
    private ServiceTypeItemBootMode|null $mode = null;

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

        $this->setup = true;
        $this->items()->fill($items);

        return $this;
    }

    public function booted(): bool
    {
        return $this->setup && $this->mode && $this->booted;
    }

    public function boot(ServiceTypeItemBootMode $mode, callable $callback): static
    {
        foreach ($this->items() as $item) {
            try {
                $callback($item['type'], $item);
            } catch (Exception $exception) {
                // TBD
            }
        }

        $this->booted = true;
        // Define latest boot mode.
        $this->mode = $mode;

        return $this;
    }

    private function items(): DataCollection
    {
        return $this->items ??= $this->dataArrayFactory->create();
    }
}
