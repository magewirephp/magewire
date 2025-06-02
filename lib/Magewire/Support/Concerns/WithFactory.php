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

trait WithFactory
{
    public function factory(array $arguments = []): static
    {
        return ObjectManager::getInstance()->create(self::class, $arguments);
    }
}
