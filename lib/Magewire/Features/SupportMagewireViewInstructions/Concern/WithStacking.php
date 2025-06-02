<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Concern;

trait WithStacking
{
    private int $stackPosition = 100;

    public function withStackPosition(int $position): static
    {
        $this->stackPosition = $position;

        return $this;
    }

    public function getStackPosition(): int
    {
        return $this->stackPosition;
    }

    public function resetStackPosition(): static
    {
        return $this->withStackPosition(100);
    }
}
