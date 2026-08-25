<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\RateLimiting;

use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterConfig;

/**
 * Fixed policy for the isolated Playwright rate-limiting fixture.
 */
class PlaywrightRateLimiterConfig extends RateLimiterConfig
{
    public const MAX_ATTEMPTS = 2;
    public const DECAY_SECONDS = 5;
    public const WARNING_THRESHOLD = 2;
    public const LOCKOUT_SECONDS = 6;

    public function throttleInDeveloperMode(): bool
    {
        return true;
    }

    public function canRateLimitRequests(): bool
    {
        return true;
    }

    public function canRateLimitComponents(): bool
    {
        return false;
    }

    public function canLockout(): bool
    {
        return true;
    }

    public function isSharedScope(): bool
    {
        return true;
    }

    public function isIsolatedScope(): bool
    {
        return false;
    }

    public function getRequestsMaxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    public function getRequestsDecaySeconds(): int
    {
        return self::DECAY_SECONDS;
    }

    public function getLockoutWarningThreshold(): int
    {
        return self::WARNING_THRESHOLD;
    }

    public function getLockoutSeconds(): int
    {
        return self::LOCKOUT_SECONDS;
    }
}
