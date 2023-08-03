<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Layout;
use Magewirephp\Magewire\Component;
use Psr\Log\LoggerInterface;

abstract class Action implements ActionInterface
{
    abstract public function handle(Component $component, array $payload);

    public function inspect(Component $component, array $updates): bool
    {
        return true;
    }

    public function evaluate(Component $component, array $updates)
    {
        return true;
    }
}
