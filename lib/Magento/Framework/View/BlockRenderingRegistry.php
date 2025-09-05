<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Framework\View;

use Magento\Framework\View\Element\BlockInterface;

class BlockRenderingRegistry
{
    private array $blocks = [];

    private BlockInterface|null $previous = null;
    private BlockInterface|null $current = null;

    public function push(BlockInterface $block): static
    {
        $this->current = $block;
        $this->blocks[] = $block;

        return $this;
    }

    public function pop(): static
    {
        $this->previous = array_pop($this->blocks);

        return $this;
    }

    public function previous(): BlockInterface|null
    {
        return $this->previous;
    }

    public function current(): BlockInterface|null
    {
        return $this->current;
    }
}
