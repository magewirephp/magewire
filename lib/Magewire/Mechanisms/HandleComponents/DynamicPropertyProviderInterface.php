<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

interface DynamicPropertyProviderInterface
{
    /**
     * Returns an array of data
     */
    public function fetch(): array;
}
