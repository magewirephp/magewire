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

class RateLimiter
{
    public function __construct(
        private readonly RateLimiterStorageInterface $storage,
        private readonly DateTime $dateTime
    ) {
        //
    }

    /**
     * Record a hit for the given key.
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $currentTime = $this->dateTime->gmtTimestamp();

        $hits = $this->getCleanHits($key, $decaySeconds, $currentTime);
        $hits[] = $currentTime;

        $this->storage->set($key, $hits, $decaySeconds);

        return count($hits);
    }

    /**
     * Validate if key is within the rate limit.
     */
    public function validate(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $hits = $this->getCleanHits($key, $decaySeconds, $this->dateTime->gmtTimestamp());

        return count($hits) < $maxAttempts;
    }

    /**
     * Reset rate limit for given key
     */
    public function reset(string $key): bool
    {
        return $this->storage->unset($key);
    }

    /**
     * Get current attempts for a key.
     */
    public function getAttempts(string $key, int $decaySeconds = 60): int
    {
        return count($this->getCleanHits($key, $decaySeconds, $this->dateTime->gmtTimestamp()));
    }

    /**
     * Get remaining attempts for a key.
     */
    public function getRemainingAttempts(string $key, int $maxAttempts, int $decaySeconds = 60): int
    {
        $current = $this->getAttempts($key, $decaySeconds);

        return max(0, $maxAttempts - $current);
    }

    /**
     * Get clean hits (remove expired ones).
     */
    private function getCleanHits(string $key, int $decaySeconds, int $currentTime): array
    {
        return array_filter($this->storage->get($key), fn ($timestamp) => ($currentTime - $timestamp) < $decaySeconds);
    }
}
