<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component;

use Magewirephp\Magewire\Component;

interface TypeCollectionInterface
{
    public function has(string $id): bool;

    public function get(string $id): Component;
}
