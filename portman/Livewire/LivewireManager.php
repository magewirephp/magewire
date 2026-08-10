<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Mechanisms\ComponentRegistry;
use Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents;

class LivewireManager extends \Livewire\LivewireManager
{
    private array $renderStack = [];

    public function __construct(
        private readonly LivewireServiceProvider $magewireServiceProvider,
        private readonly ComponentRegistry $componentRegistry
    ) {
        //
    }

    public function new($name, $id = null)
    {
        return $this->componentRegistry->new($name, $id);
    }

    /**
     * @throws NotFoundException
     */
    public function mount($name, $params = [], $key = null, AbstractBlock|null $block = null, Component|null $component = null): void
    {
        /** @var HandleComponents $handleComponentsMechanism */
        $handleComponentsMechanism = $this->magewireServiceProvider->getHandleComponentsMechanism();

        $this->renderStack[$block->getNameInLayout()] = $handleComponentsMechanism
            ->mount($name, $params, $block->getCacheKey(), $block, $component);
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     * @throws NotFoundException
     */
    public function update($snapshot, $diff, $calls, AbstractBlock|null $block = null): void
    {
        /** @var HandleComponents $handleComponentsMechanism */
        $handleComponentsMechanism = $this->magewireServiceProvider->getHandleComponentsMechanism();

        $this->renderStack[$block->getNameInLayout()] = $handleComponentsMechanism
            ->update($snapshot->toArray(), $diff, $calls, $block);
    }

    public function render(AbstractBlock $block, string $html)
    {
        $name = $block->getNameInLayout();

        /*
         * A block reaches this point without a renderer whenever mount or update failed: the
         * exception manager swaps in its own template and lets rendering continue, so nothing was
         * ever stacked. Returning the HTML untouched keeps that recovery intact rather than
         * turning it into a fatal.
         */
        if (! isset($this->renderStack[$name])) {
            return $html;
        }

        $renderer = $this->renderStack[$name];

        /*
         * Removed by key rather than popped. Blocks finish rendering innermost first, so the last
         * entry is not necessarily this block's, and popping would evict a sibling or parent that
         * still has to render.
         */
        unset($this->renderStack[$name]);

        return $renderer($block, $html);
    }
}
