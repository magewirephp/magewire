<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Concerns;

use Magento\Framework\App\ObjectManager;

trait AsFactory
{
    abstract private function newInstanceType(): string;

    private function newInstance(array $arguments = [])
    {
        return ObjectManager::getInstance()->create($this->newInstanceType());
    }
}
