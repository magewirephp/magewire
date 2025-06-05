<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

use Magewirephp\Magewire\Model\View\Fragment;
use Throwable;

abstract class Modifier
{
    /**
     * Applies a transformation to the fragment output.
     *
     * @throws Throwable
     */
    abstract public function modify(Fragment $fragment): Fragment;
}
