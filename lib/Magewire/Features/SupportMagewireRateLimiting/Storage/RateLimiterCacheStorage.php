<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Storage;

use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterCache;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterStorageInterface;

class RateLimiterCacheStorage implements RateLimiterStorageInterface
{
    const CACHE_TAG = 'rate_limiter';

    public function __construct(
        private readonly RateLimiterCache $cache,
    ) {
        //
    }

    public function get(string $key): array
    {
        $storage = $this->cache->fetch();

        return $storage[$key] ?? [];
    }

    public function set(string $key, array $data, int|null $ttl = null): bool
    {
        $storage = $this->cache->fetch();
        $storage[$key] = $data;

        return $this->cache->save($storage, $ttl);
    }

    public function unset(string $key): bool
    {
        $storage = $this->cache->fetch();
        unset($storage[$key]);

        return $this->cache->save($storage);
    }
}
