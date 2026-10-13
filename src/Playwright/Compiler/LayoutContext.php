<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Playwright\Compiler;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutLifecycleManager;

class LayoutContext implements ArgumentInterface
{
    public function __construct(
        private readonly LayoutLifecycleManager $layoutLifecycleManager
    ) {
    }

    public function route(): string
    {
        return $this->layoutLifecycleManager->forMagewire()->route();
    }

    public function within(string $name): bool
    {
        return $this->layoutLifecycleManager->forMagewire()->within($name);
    }
}
