<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\LazyLoading;

/**
 * Eagerly rendered parent whose template embeds a lazy child component. Nothing about it
 * is lazy, so it proves a lazy child neither defers nor disturbs its host: the host stays
 * interactive across the child's lazy commit.
 */
class Host extends Basic
{
}
