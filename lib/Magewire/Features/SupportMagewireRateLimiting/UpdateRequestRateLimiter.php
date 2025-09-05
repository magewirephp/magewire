<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;

class UpdateRequestRateLimiter extends RateLimiter
{
    public function __construct(
        private readonly RateLimiterStorageInterface $storage,
        private readonly DateTime $datetime,
        private readonly RateLimiterConfig $rateLimiterConfig
    ) {
        parent::__construct($this->storage, $this->datetime);
    }

    public function validateWithComponentRequestContext(ComponentRequestContext $componentRequestContext): bool
    {
        $key = $this->generateKeyByRequestContext($componentRequestContext);
        $attempts = $this->rateLimiterConfig->getRequestsMaxAttempts();
        $decay = $this->rateLimiterConfig->getRequestsDecaySeconds();

        if ($result = $this->validate($key, $attempts, $decay)) {
            $this->hit($key);
        }

        return $result;
    }

    public function validateWithComponent(Component $component): bool
    {
        $key = $this->generateKeyByComponent($component);

        $component->tap();

        if ($result = $this->validate($key, 4, 5)) {
            $this->hit($key);
        }

        return $result;
    }

    private function generateKeyByRequestContext(ComponentRequestContext $componentRequestContext): string
    {
        $key = '123@RL';

        if ($this->rateLimiterConfig->isIsolatedScope()) {
            $key .= $componentRequestContext->getSnapshot()->getMemoValue('id');
        }

        return $key;
    }

    private function generateKeyByComponent(Component $component): string
    {
        return '123@RLC' . $component->id();
    }
}
