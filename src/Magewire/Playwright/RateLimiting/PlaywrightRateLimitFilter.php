<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\RateLimiting;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Filter\RateLimitFilter;
use Magewirephp\Magewire\Mechanisms\HandleRequests\Filter\RequestFilterInterface;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;
use Throwable;

/**
 * Applies the real rate-limit filter only to the dedicated Playwright component.
 *
 * The production filter remains configuration-driven and global. Scoping this delegate by the
 * verified component snapshot keeps the browser fixture deterministic without throttling other
 * Playwright specs running against the same store.
 */
class PlaywrightRateLimitFilter implements RequestFilterInterface
{
    private const COMPONENT_PREFIX = 'magewire.playwright.rate-limiting';

    public function __construct(
        private readonly RateLimitFilter $rateLimitFilter,
        private readonly ApplicationState $applicationState
    ) {
    }

    public function check(RequestContext $context): void
    {
        if ($this->isProduction()) {
            return;
        }

        foreach ($context->getComponents() as $componentRequestContext) {
            $id = (string) $componentRequestContext->getSnapshot()->getMemoValue('id');

            if (str_starts_with($id, self::COMPONENT_PREFIX)) {
                $this->rateLimitFilter->check($context);

                return;
            }
        }
    }

    private function isProduction(): bool
    {
        try {
            return $this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION;
        } catch (Throwable) {
            return true;
        }
    }
}
