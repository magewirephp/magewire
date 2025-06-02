<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

use Closure;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Exceptions\MethodNotFoundException;
use Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents as HandleComponentsMechanism;

class HandleComponentsFacade
{
    public function __construct(
        private readonly HandleComponentsMechanism $mechanism
    ) {
        //
    }

    /**
     * @throws ComponentNotFoundException
     * @throws FileSystemException
     * @throws MethodNotFoundException
     * @throws RuntimeException
     */
    public function update(Snapshot $snapshot, array $updates, array $calls, AbstractBlock $block): Closure
    {
        return $this->mechanism->update($snapshot->toArray(), $updates, $calls, $block);
    }

    public function mount(string $name, array $params, AbstractBlock $block, Component $component): Closure
    {
        return $this->mechanism->mount($name, $params, $block->getCacheKey(), $block, $component);
    }
}
