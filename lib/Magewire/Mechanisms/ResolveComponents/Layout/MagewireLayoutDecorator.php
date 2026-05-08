<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout;

use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magento\Framework\View\DynamicLayoutBuilder;
use Magewirephp\Magento\Framework\View\Layout\GeneratorPool;

class MagewireLayoutDecorator extends LayoutDecorator
{
    public function __construct(
        private GeneratorPool $generatorPool,
        private DynamicLayoutBuilder $dynamicLayoutBuilder
    ) {
    }

    /**
     * Decorates the given layout to support block loading without a page root.
     *
     * Normally a block structure requires a page as its root parent to bind upon.
     * This decorator simulates that relationship by introducing a fictional container,
     * allowing blocks to resolve their parent without an actual page being present.
     */
    public function decorateForPagelessBlockFetching(LayoutInterface $layout): LayoutInterface
    {
        if ($layout instanceof Layout) {
            $builder = $this->dynamicLayoutBuilder->newInstance(['layout' => $layout]);

            // Custom generator pool limiting the allowed generators to only blocks and containers.
            $layout->setGeneratorPool($this->generatorPool);
            // Custom builder to limit the amount of rebuild for repetitive layouts.
            $layout->setBuilder($builder);
        }

        return $layout;
    }
}
