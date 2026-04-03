<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\Management;

use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout\LayoutDecorator;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout\LayoutLifecycle;

class LayoutManager
{
    public function __construct(
        private readonly LayoutInterface $layout,
        private readonly LayoutFactory $factory,
        private readonly LayoutDecorator $decorator,
        private readonly LayoutLifecycleManager $lifecycleManager,
    ) {
    }

    /**
     * Returns the global layout singleton.
     */
    public function singleton(): LayoutInterface
    {
        return $this->layout;
    }

    /**
     * Returns a new Layout instance.
     */
    public function factory(): LayoutFactory
    {
        return $this->factory;
    }

    public function decorator(): LayoutDecorator
    {
        return $this->decorator;
    }

    public function lifecycle(string $name = 'magewire'): LayoutLifecycle
    {
        return $this->lifecycleManager->target($name);
    }
}
