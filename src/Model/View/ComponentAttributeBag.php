<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magewirephp\Magewire\Support\DataCollection;
use Stringable;

class ComponentAttributeBag extends DataCollection implements Stringable
{
    public function __toString()
    {
        return '';
    }
}
