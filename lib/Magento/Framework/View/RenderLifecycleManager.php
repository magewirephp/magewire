<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Framework\View;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use function Magewirephp\Magewire\trigger;

class RenderLifecycleManager
{
    private array $blocks = [];
    private bool $running = false;

    private BlockInterface|null $first = null;
    private BlockInterface|null $previous = null;
    private BlockInterface|null $current = null;

    public function push(AbstractBlock|BlockInterface $block): static
    {
        if (! $this->first) {
            $this->first = $block;
        }

        $this->current  = $block;
        $this->blocks[] = $block;

        trigger('magewire:render:start', $this, $block);

        return $this;
    }

    public function pop(): static
    {
        $this->previous = array_pop($this->blocks);

        trigger('magewire:render:end', $this, $this->previous);

        return $this;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function previousBlock(): BlockInterface|null
    {
        return $this->previous;
    }

    public function currentBlock(): BlockInterface|null
    {
        return $this->current;
    }
}
