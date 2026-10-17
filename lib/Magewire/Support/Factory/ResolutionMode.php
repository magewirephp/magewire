<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Factory;

/** @internal The public API expresses these choices through method names. */
enum ResolutionMode
{
    case Configured;
    case Shared;
    case Fresh;
}
