<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Config\Source\RateLimitingVariant;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Config\Source\RequestsScope;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewireGroup;

/** @mago-expect lint:too-many-methods */
class RateLimiterConfig extends ConfigMagewireGroup
{
    public function throttleInDeveloperMode(): bool
    {
        return (bool) ( $this->config()->getFeaturesGroupValue('rate_limiting/developer_mode') ?? false );
    }

    public function canRateLimitRequests(): bool
    {
        return $this->getRateLimitingVariant() === RateLimitingVariant::REQUESTS_ONLY;
    }

    public function canRateLimitComponents(): bool
    {
        return $this->getRateLimitingVariant() === RateLimitingVariant::COMPONENTS_ONLY;
    }

    public function canRateLimit(): bool
    {
        return $this->canRateLimitRequests() || $this->canRateLimitComponents();
    }

    public function canLockout(): bool
    {
        return (bool) ( $this->config()->getFeaturesGroupValue('rate_limiting/lockout/enabled') ?? false );
    }

    public function isSharedScope(): bool
    {
        return $this->getRateLimitingScope() === RequestsScope::SHARED;
    }

    public function isIsolatedScope(): bool
    {
        return $this->getRateLimitingScope() === RequestsScope::ISOLATED;
    }

    public function getRequestsMaxAttempts(): int
    {
        return (int) ( $this->config()->getFeaturesGroupValue('rate_limiting/requests/max_attempts') ?? 4 );
    }

    public function getRequestsDecaySeconds(): int
    {
        return (int) ( $this->config()->getFeaturesGroupValue('rate_limiting/requests/decay_seconds') ?? 5 );
    }

    public function getLockoutWarningThreshold(): int
    {
        return max(1, (int) ( $this->config()->getFeaturesGroupValue('rate_limiting/lockout/warning_threshold') ?? 3 ));
    }

    public function getLockoutSeconds(): int
    {
        return max(1, (int) ( $this->config()->getFeaturesGroupValue('rate_limiting/lockout/duration_seconds') ?? 60 ));
    }

    protected function getRateLimitingVariant(): string
    {
        return $this->config()->getFeaturesGroupValue('rate_limiting/variant') ?? RateLimitingVariant::NONE;
    }

    protected function getRateLimitingScope(): string
    {
        return $this->config()->getFeaturesGroupValue('rate_limiting/requests/scope') ?? RequestsScope::SHARED;
    }
}
