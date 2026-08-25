<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterStorageInterface;

class InMemoryRateLimiterStorage implements RateLimiterStorageInterface
{
    /** @var array<string, array<int, int>> */
    private array $values = [];

    public function get(string $key): array
    {
        return $this->values[$key] ?? [];
    }

    public function set(string $key, array $data, int $ttl): bool
    {
        $this->values[$key] = $data;

        return true;
    }

    public function unset(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    /** @return array<string, array<int, int>> */
    public function all(): array
    {
        return $this->values;
    }
}
