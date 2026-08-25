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
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestFingerprint;

/**
 * Escalates repeated rate-limit rejections into a temporary, origin-wide lockout.
 *
 * Warnings use the same decay window as the limiter that raised them. A visitor therefore has to
 * trigger the configured number of rejections inside one active window; old warnings do not build
 * up into a lock much later.
 */
class RateLimitLockout
{
    private const KEY_PREFIX = 'magewire@rate-limit-lockout@';

    public function __construct(
        private readonly RateLimiterStorageInterface $storage,
        private readonly DateTime $dateTime,
        private readonly RateLimiterConfig $config,
        private readonly RequestFingerprint $requestFingerprint
    ) {
    }

    /**
     * Return the number of whole seconds left on the current origin's lockout.
     */
    public function remainingSeconds(): int
    {
        if (! $this->config->canLockout()) {
            return 0;
        }

        $key = $this->lockKey();
        $lockedUntil = (int) ( $this->storage->get($key)[0] ?? 0 );
        $remaining = $lockedUntil - $this->dateTime->gmtTimestamp();

        if ($remaining <= 0 && $lockedUntil !== 0) {
            $this->storage->unset($key);
        }

        return max(0, $remaining);
    }

    /**
     * Record a customer-visible rate-limit rejection.
     *
     * Returns the lockout duration when this rejection starts a lock, the remaining duration when
     * a concurrent request finds an existing lock, or zero while the warning threshold has not
     * been reached.
     */
    public function registerRejection(int $decaySeconds): int
    {
        if (! $this->config->canLockout()) {
            return 0;
        }

        $remaining = $this->remainingSeconds();

        if ($remaining > 0) {
            return $remaining;
        }

        $decaySeconds = max(1, $decaySeconds);
        $currentTime = $this->dateTime->gmtTimestamp();
        $warningKey = $this->warningKey();
        $warnings = array_values(array_filter(
            $this->storage->get($warningKey),
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp <= $currentTime && ( $currentTime - $timestamp ) < $decaySeconds
        ));

        $warnings[] = $currentTime;

        if (count($warnings) < $this->config->getLockoutWarningThreshold()) {
            $this->storage->set($warningKey, $warnings, $decaySeconds);

            return 0;
        }

        $duration = $this->config->getLockoutSeconds();

        $this->storage->unset($warningKey);
        $this->storage->set($this->lockKey(), [$currentTime + $duration], $duration);

        return $duration;
    }

    private function warningKey(): string
    {
        return $this->key('warnings');
    }

    private function lockKey(): string
    {
        return $this->key('lock');
    }

    private function key(string $suffix): string
    {
        return self::KEY_PREFIX . $this->requestFingerprint->resolve() . '@' . $suffix;
    }
}
