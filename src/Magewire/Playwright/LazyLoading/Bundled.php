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
 * Lazy with isolation disabled: its lazy-load commit may bundle with other
 * non-isolated lazy commits. Surfaces as memo.lazyIsolated === false.
 */
#[Lazy(isolate: false)]
class Bundled extends Basic
{
}
