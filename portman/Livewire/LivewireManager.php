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
        $renderer = $this->renderStack[$block->getNameInLayout()];

        array_pop($this->renderStack);

        return $renderer($block, $html);
    }
}
