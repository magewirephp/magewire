<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Component;

use Magewirephp\Magewire\Component;

abstract class Dynamic extends Component
{
    public const COMPONENT_TYPE = 'dynamic';

    abstract public function getTemplate(): string;
}
