<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\LazyLoading;

use Magewirephp\Magewire\Attributes\Lazy;

/**
 * On-load trigger selected on the attribute itself, without a layout argument. Sits below
 * the fold, so it may only load because of its mode — never because it came into view.
 */
#[Lazy(mode: 'on-load')]
class AttributedOnLoad extends Basic
{
}
