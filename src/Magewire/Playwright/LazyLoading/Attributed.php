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
 * Lazy-loading opted in purely through the #[Lazy] attribute (default isolation).
 * Without an "on-load" layout argument it defaults to the on-intersect trigger.
 */
#[Lazy]
class Attributed extends Basic
{
}
