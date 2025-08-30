<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

interface RateLimiterStorageInterface
{
    /**
     * Get stored data for a key.
     */
    public function get(string $key): array;

    /**
     * Store data for a key with TTL.
     */
    public function set(string $key, array $data, int $ttl): bool;

    /**
     * Remove data for a key.
     */
    public function unset(string $key): bool;
}
