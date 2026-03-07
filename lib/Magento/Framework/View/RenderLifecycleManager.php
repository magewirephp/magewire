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
    /** @var array<string, BlockInterface $blocks */
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

        $this->previous = $this->current;
        $this->current  = $block;
        $this->blocks[$block->getNameInLayout()] = $block;
        $this->running  = true;

        trigger('magewire:render:start', $this, $block);

        return $this;
    }

    public function pop(): static
    {
        $popped = array_pop($this->blocks);

        trigger('magewire:render:end', $this, $popped);

        if (empty($this->blocks)) {
            $this->running  = false;
            $this->current  = null;
            $this->previous = null;
        } else {
            $values         = array_values($this->blocks);
            $this->current  = end($values);
            $this->previous = count($values) >= 2 ? $values[count($values) - 2] : null;
        }

        return $this;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function firstBlock(): BlockInterface|null
    {
        return $this->first;
    }

    public function previousBlock(): BlockInterface|null
    {
        return $this->previous;
    }

    public function currentBlock(): BlockInterface|null
    {
        return $this->current;
    }

    /**
     * Determines whether the given block is currently being tracked in the lifecycle.
     *
     * This can be handy in cases where you need to know if the current handled block sits somewhere
     * nested within the given block (name).
     */
    public function isWithin(AbstractBlock|string $block): bool
    {
        if (! is_string($block)) {
            $block = $block->getNameInLayout();
        }

        return array_key_exists($block, $this->blocks);
    }
}
