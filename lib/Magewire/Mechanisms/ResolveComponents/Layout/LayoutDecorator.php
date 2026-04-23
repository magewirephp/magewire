<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout;

use Magento\Framework\View\LayoutInterface;

abstract class LayoutDecorator
{
    abstract public function decorateForPagelessBlockFetching(LayoutInterface $layout): LayoutInterface;
}
