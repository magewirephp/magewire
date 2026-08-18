<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use Throwable;

use function Magewirephp\Magewire\on;

/**
 * Component scoped rate limiting.
 *
 * The request scoped variant lives in the request filter pipeline, where it rejects abuse before
 * anything is reconstructed. The component variant cannot move there: it keys on a constructed
 * component, which only exists once reconstruction has already happened.
 *
 * @see \Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Filter\RateLimitFilter
 */
class SupportMagewireRateLimiting extends ComponentHook
{
    public function __construct(
        private readonly UpdateRequestRateLimiter $rateLimiter,
        private readonly RateLimiterConfig $rateLimiterConfig,
        private readonly ApplicationState $appState
    ) {
    }

    public function provide(): void
    {
        // Rate limiting is always enforced in production. In developer/default mode it is skipped
        // (rapid interaction during local dev / automated tests would otherwise hit spurious
        // TooManyRequests), unless a developer explicitly opts in via the system config toggle.
        if ($this->getAppMode() !== ApplicationState::MODE_PRODUCTION && ! $this->rateLimiterConfig->throttleInDeveloperMode()) {
            return;
        }

        if ($this->rateLimiterConfig->canRateLimitComponents()) {
            on('magewire:component:reconstruct', function () {
                // Apply a rate limit check for the component after the component reconstruction.
                return function (Template $block) {
                    $component = $block->getData('magewire');

                    // Component scope rate limiting validation.
                    if ($component instanceof Component && ! $this->rateLimiter->validateWithComponent($component)) {
                        throw new TooManyRequestsException();
                    }
                };
            });
        }
    }

    private function getAppMode(): string
    {
        try {
            return $this->appState->getMode();
        } catch (Throwable $exception) {
            return ApplicationState::MODE_PRODUCTION;
        }
    }
}
