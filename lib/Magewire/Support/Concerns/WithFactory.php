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

/**
 * WIP...
 */
trait WithFactory
{
    /**
     * Returns a new instance of the current object.
     */
    public function newInstance(array $arguments = []): static
    {
        return ObjectManager::getInstance()->create(static::class, $arguments);
    }
}
