<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Config\Source\Enabled;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewireGroup;

class RateLimiterConfig extends ConfigMagewireGroup
{
    public function canRateLimitRequests(): bool
    {
        $enabled = $this->config()->getFeaturesGroupValue('rate_limiter/enabled') ?? Enabled::NONE;

        return $enabled === Enabled::REQUESTS_ONLY;
    }

    public function canRateLimitComponents(): bool
    {
        $enabled = $this->config()->getFeaturesGroupValue('rate_limiter/enabled') ?? Enabled::NONE;

        return $this->canRateLimitRequests() === false && $enabled === Enabled::COMPONENTS_ONLY;
    }

    public function getRequestsMaxAttempts(): int
    {
        return (int) $this->config()->getFeaturesGroupValue('rate_limiter/requests/max_attempts') ?? 4;
    }

    public function getRequestsDecaySeconds(): int
    {
        return (int) $this->config()->getFeaturesGroupValue('rate_limiter/requests/decay_seconds') ?? 5;
    }
}
