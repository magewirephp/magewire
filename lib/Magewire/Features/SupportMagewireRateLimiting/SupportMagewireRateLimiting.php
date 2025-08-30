<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use function Magewirephp\Magewire\on;

class SupportMagewireRateLimiting extends ComponentHook
{
    public function __construct(
        private readonly UpdateRequestRateLimiter $rateLimiter
    ) {
        //
    }

    public function provide(): void
    {
        on('request', function(array $payload) {
            $context = $payload[0] ?? false;

            // Global scope rate limiting validation.
            if ($context && ! $this->rateLimiter->validateWithComponentRequestContext($context)) {
                throw new TooManyRequestsException();
            }
        });

        on('magewire:reconstruct', function($a) {
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
