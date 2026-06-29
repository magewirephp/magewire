<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment\Concern;

use Magewirephp\Magewire\Model\View\PlacementEntry;
use Magewirephp\Magewire\Model\View\PlacementRegistry;
use Magewirephp\Magewire\Support\Factory;

trait Placeable
{
    private string|null $placement = null;

    abstract protected function placements(): PlacementRegistry;

    public function placement(string $placement): static
    {
        $this->placement = $placement;

        return $this;
    }

    public function for(string $placement): static
    {
        return $this->placement($placement);
    }

    protected function echo(string $output): void
    {
        if ($this->placement === null) {
            echo $output;
            return;
        }

        $entry = Factory::create(PlacementEntry::class, [
            'content' => $output,
            'scope' => 'script',
            'name' => $this->placement
        ]);

        $this->placements()->script($this->placement, $entry);

        echo $entry->sourceComment();
    }
}
