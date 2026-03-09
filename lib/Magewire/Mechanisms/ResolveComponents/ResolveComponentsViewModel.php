<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\RenderLifecycleManager;

class ResolveComponentsViewModel implements ArgumentInterface
{
    public function __construct(
        private readonly RenderLifecycleManager $renderLifecycleManager,
    ) {
        
    }

    public function doesPageHaveComponents(): bool
    {
        return count($this->renderLifecycleManager->getLifecycle()) > 0;
    }
}
