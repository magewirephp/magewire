<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\Framework\View;

use Magento\Framework\View\Layout;
use Magewirephp\Magento\Framework\View\Layout\GeneratorPool;

class DynamicLayoutDecorator
{
    public function __construct(
        private readonly GeneratorPool $magewireGeneratorPool,
        private readonly DynamicLayoutBuilderFactory $dynamicLayoutBuilderFactory
    ) {
    }

    public function decorate(Layout $layout): Layout
    {
        $builder = $this->dynamicLayoutBuilderFactory->create(['layout' => $layout]);

        $layout->setGeneratorPool($this->magewireGeneratorPool);
        $layout->setBuilder($builder);

        return $layout;
    }
}
