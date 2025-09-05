<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoObserverEvents\DTO;

use Magewirephp\Magewire\Support\Contracts\DataTransferObjectInterface;

class ListenerDataTransferObject implements DataTransferObjectInterface
{
    /** @var array<int, callable> */
    private array $listeners = [];

    public function with(callable $callback): static
    {
        $this->listeners[] = $callback;

        return $this;
    }

    public function listeners(): array
    {
        return $this->listeners;
    }
}
