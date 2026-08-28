<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Storage;

use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\App\Cache\Type\Magewire as MagewireCacheType;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterStorageInterface;

/**
 * Stores lockout counters separately per request origin.
 *
 * The regular rate limiter keeps all attempt counters in one cache section. A short attempt TTL
 * must not be able to expire an unrelated, longer lockout, so lockout keys use individual cache
 * entries instead.
 */
class RateLimitLockoutCacheStorage implements RateLimiterStorageInterface
{
    private const CACHE_TAG = 'rate_limiter_lockout';
    private const IDENTIFIER_PREFIX = 'rate-limiter-lockout-';

    public function __construct(
        private readonly MagewireCacheType $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function get(string $key): array
    {
        $data = $this->cache->load($this->identifier($key));

        return is_array($data) ? $data : [];
    }

    public function set(string $key, array $data, int $ttl): bool
    {
        return $this->cache->save($this->serializer->serialize(array_values($data)), $this->identifier($key), [self::CACHE_TAG], max(1, $ttl));
    }

    public function unset(string $key): bool
    {
        return $this->cache->remove($this->identifier($key));
    }

    private function identifier(string $key): string
    {
        return self::IDENTIFIER_PREFIX . hash('sha256', $key);
    }
}
