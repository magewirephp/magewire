<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Filter;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterConfig;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\UpdateRequestRateLimiter;
use Magewirephp\Magewire\Mechanisms\HandleRequests\Filter\RequestFilterInterface;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;
use Throwable;

/**
 * Rejects update requests that exceed the configured request budget.
 *
 * Runs first in the pipeline: it is cheap, and obvious abuse should be turned away before any
 * costlier filter reaches out to an external service.
 *
 * Only the request variant is enforced here. The component variant needs a constructed component
 * to key on, which by definition does not exist yet at this point in the request, and therefore
 * stays behind on the component reconstruction hook.
 *
 * @see \Magewirephp\Magewire\Features\SupportMagewireRateLimiting\SupportMagewireRateLimiting
 */
class RateLimitFilter implements RequestFilterInterface
{
    public const ATTRIBUTE = 'rate_limit';

    public function __construct(
        private readonly UpdateRequestRateLimiter $rateLimiter,
        private readonly RateLimiterConfig $rateLimiterConfig,
        private readonly ApplicationState $applicationState
    ) {
    }

    public function check(RequestContext $context): void
    {
        if (! $this->canRateLimit()) {
            return;
        }

        $passed = $this->rateLimiter->validateWithRequestContext($context);

        /*
         * Published regardless of the outcome, so later filters can tell an untouched request
         * apart from one that came close to its budget.
         */
        $context->attributes()->set(self::ATTRIBUTE, $passed);

        if (! $passed) {
            throw new TooManyRequestsException();
        }
    }

    /**
     * Rate limiting is always enforced in production. In developer/default mode it is skipped,
     * since rapid interaction during local development or automated tests would otherwise hit
     * spurious rejections, unless a developer explicitly opts in via the system config toggle.
     */
    private function canRateLimit(): bool
    {
        if ($this->getApplicationMode() !== ApplicationState::MODE_PRODUCTION && ! $this->rateLimiterConfig->throttleInDeveloperMode()) {
            return false;
        }

        return $this->rateLimiterConfig->canRateLimitRequests();
    }

    /**
     * Falls back to production so an unreadable mode keeps rate limiting on. Guessing developer
     * here would silently switch protection off exactly when the application is least healthy.
     */
    private function getApplicationMode(): string
    {
        try {
            return $this->applicationState->getMode();
        } catch (Throwable) {
            return ApplicationState::MODE_PRODUCTION;
        }
    }
}
