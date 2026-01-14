<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\FragmentFactory;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use Magewirephp\Magewire\Model\View\UtilsInterface;

class Slots implements UtilsInterface
{
    public function __construct(
        private SlotsRegistry $slotsRegistry,
        private FragmentFactory $fragmentFactory
    ) {
        //
    }

    public function bind(string $name, AbstractBlock $block)
    {
        return $this->fragmentFactory->slot($name, $block);
    }

    public function print(string $name): string
    {
        return $this->slotsRegistry->print($name);
    }

    public function exists(string $name): bool
    {
        return $this->slotsRegistry->has($name);
    }
}
