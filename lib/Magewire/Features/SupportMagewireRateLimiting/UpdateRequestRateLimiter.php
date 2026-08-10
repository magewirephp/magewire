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
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestFingerprint;

class UpdateRequestRateLimiter extends RateLimiter
{
    public function __construct(
        private readonly RateLimiterStorageInterface $storage,
        private readonly DateTime $datetime,
        private readonly RateLimiterConfig $rateLimiterConfig,
        private readonly RequestFingerprint $requestFingerprint
    ) {
        parent::__construct($this->storage, $this->datetime);
    }

    /**
     * Validate an entire update request.
     *
     * Under a shared scope the request counts as a single attempt, no matter how many components
     * it carries. Under an isolated scope every component keeps its own budget, and a single
     * exhausted component rejects the request.
     */
    public function validateWithRequestContext(RequestContext $context): bool
    {
        if (! $this->rateLimiterConfig->isIsolatedScope()) {
            return $this->consume($this->generateKey());
        }

        foreach ($context->getComponents() as $componentRequestContext) {
            if (! $this->consume($this->generateKeyByComponentRequestContext($componentRequestContext))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @deprecated Superseded by validateWithRequestContext(), which sees the whole payload rather
     *             than a single component of it.
     * @see UpdateRequestRateLimiter::validateWithRequestContext()
     */
    public function validateWithComponentRequestContext(ComponentRequestContext $componentRequestContext): bool
    {
        return $this->consume(
            $this->rateLimiterConfig->isIsolatedScope()
                ? $this->generateKeyByComponentRequestContext($componentRequestContext)
                : $this->generateKey()
        );
    }

    public function validateWithComponent(Component $component): bool
    {
        /*
         * The component variant has no configuration of its own, and the "requests" configuration
         * belongs to the request variant. Both are mutually exclusive, so the original fixed budget
         * is kept here rather than silently adopting values meant for the other variant.
         */
        return $this->consume($this->generateKeyByComponent($component), 4, 5);
    }

    /**
     * Validate the key and, while still within budget, record the attempt.
     */
    private function consume(string $key, int|null $attempts = null, int|null $decay = null): bool
    {
        $attempts ??= $this->rateLimiterConfig->getRequestsMaxAttempts();
        $decay ??= $this->rateLimiterConfig->getRequestsDecaySeconds();

        $result = $this->validate($key, $attempts, $decay);

        if ($result) {
            $this->hit($key, $decay);
        }

        return $result;
    }

    private function generateKey(string $suffix = ''): string
    {
        return 'magewire@rate-limit@' . $this->requestFingerprint->resolve() . $suffix;
    }

    private function generateKeyByComponentRequestContext(ComponentRequestContext $componentRequestContext): string
    {
        return $this->generateKey('@' . $componentRequestContext->getSnapshot()->getMemoValue('id'));
    }

    private function generateKeyByComponent(Component $component): string
    {
        return $this->generateKey('@' . $component->id());
    }
}
