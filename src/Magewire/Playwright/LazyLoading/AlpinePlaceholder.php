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
 * Guards the placeholder root against attribute takeover: a developer who binds their
 * own Alpine component onto the root must keep it, so the lazy trigger may not inject
 * an x-data of its own there.
 */
class AlpinePlaceholder extends Basic
{
    public function placeholder(array $params = []): string
    {
        return 'Magewirephp_Magewire::tests/magewire/playwright/lazy_loading/alpine_placeholder.phtml';
    }
}
