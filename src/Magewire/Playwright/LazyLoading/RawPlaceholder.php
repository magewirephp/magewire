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
 * Exercises the raw-HTML placeholder branch: placeholder() returns markup directly
 * instead of a template id. Markup must have a single root element.
 */
class RawPlaceholder extends Basic
{
    public function placeholder(array $params = []): string
    {
        return '<div><span data-testid="lazy-raw-placeholder" class="animate-pulse">Raw placeholder…</span></div>';
    }
}
