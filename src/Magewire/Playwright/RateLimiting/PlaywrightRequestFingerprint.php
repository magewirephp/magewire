<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\RateLimiting;

use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestFingerprint;

/**
 * Keeps fixture counters separate from an administrator-enabled production policy.
 */
class PlaywrightRequestFingerprint extends RequestFingerprint
{
    public function __construct(
        private readonly RequestFingerprint $requestFingerprint
    ) {
    }

    public function resolve(): string
    {
        return 'playwright-rate-limiting@' . $this->requestFingerprint->resolve();
    }
}
