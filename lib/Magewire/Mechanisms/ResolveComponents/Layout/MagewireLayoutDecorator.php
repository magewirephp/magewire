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

    public function decorateForPagelessBlockFetching(LayoutInterface $layout): LayoutInterface
    {
        if ($layout instanceof Layout) {
            $builder = $this->dynamicLayoutBuilder->newInstance(['layout' => $layout]);

            $layout->setGeneratorPool($this->generatorPool);
            $layout->setBuilder($builder);
        }

        return $layout;
    }
}
