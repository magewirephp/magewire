<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportHyvaCheckoutBackwardsCompatibility;

class TemporaryHydrationRegistry
{
    private array $items = [];

    public function push(string $id): static
    {
        $this->items[] = $id;
        return $this;
    }

    public function pop(?string $id = null): static
    {
        unset($this->items[$id]);
        return $this;
    }

    public function list(): array
    {
        return $this->items;
    }
}
