<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireRateLimiting;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterConfig;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterStorageInterface;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimitLockout;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestFingerprint;
use Magewirephp\Magewire\Tests\Unit\Fixtures\InMemoryRateLimiterStorage;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Fixtures/InMemoryRateLimiterStorage.php';

class RateLimitLockoutTest extends TestCase
{
    public function test_it_locks_the_origin_when_the_warning_threshold_is_reached(): void
    {
        $now = 1_000;
        $storage = new InMemoryRateLimiterStorage();
        $lockout = $this->createLockout($storage, $now, warningThreshold: 3, durationSeconds: 60);

        self::assertSame(0, $lockout->registerRejection(5));
        self::assertSame(0, $lockout->registerRejection(5));
        self::assertSame(60, $lockout->registerRejection(5));
        self::assertSame(60, $lockout->remainingSeconds());
    }

    public function test_it_reports_the_remaining_time_and_releases_an_expired_lock(): void
    {
        $now = 1_000;
        $storage = new InMemoryRateLimiterStorage();
        $lockout = $this->createLockout($storage, $now, warningThreshold: 1, durationSeconds: 60);

        self::assertSame(60, $lockout->registerRejection(5));

        $now = 1_041;

        self::assertSame(19, $lockout->remainingSeconds());

        $now = 1_060;

        self::assertSame(0, $lockout->remainingSeconds());
        self::assertSame([], $storage->all());
    }

    public function test_warnings_outside_the_rate_limit_window_do_not_accumulate(): void
    {
        $now = 1_000;
        $storage = new InMemoryRateLimiterStorage();
        $lockout = $this->createLockout($storage, $now, warningThreshold: 2, durationSeconds: 60);

        self::assertSame(0, $lockout->registerRejection(5));

        $now = 1_006;

        self::assertSame(0, $lockout->registerRejection(5));
        self::assertSame(0, $lockout->remainingSeconds());
    }

    public function test_lockouts_are_isolated_by_request_fingerprint(): void
    {
        $now = 1_000;
        $storage = new InMemoryRateLimiterStorage();
        $first = $this->createLockout($storage, $now, fingerprint: 'first', warningThreshold: 1);
        $second = $this->createLockout($storage, $now, fingerprint: 'second', warningThreshold: 1);

        self::assertSame(60, $first->registerRejection(5));
        self::assertSame(60, $first->remainingSeconds());
        self::assertSame(0, $second->remainingSeconds());
    }

    public function test_it_does_not_write_state_when_lockouts_are_disabled(): void
    {
        $now = 1_000;
        $storage = new InMemoryRateLimiterStorage();
        $lockout = $this->createLockout($storage, $now, enabled: false, warningThreshold: 1);

        self::assertSame(0, $lockout->registerRejection(5));
        self::assertSame(0, $lockout->remainingSeconds());
        self::assertSame([], $storage->all());
    }

    /** @mago-expect lint:no-boolean-flag-parameter */
    private function createLockout(
        RateLimiterStorageInterface $storage,
        int &$now,
        string $fingerprint = 'origin',
        bool $enabled = true,
        int $warningThreshold = 3,
        int $durationSeconds = 60
    ): RateLimitLockout {
        $dateTime = $this->createStub(DateTime::class);
        $dateTime
            ->method('gmtTimestamp')
            ->willReturnCallback(static function () use (&$now): int {
                return $now;
            });

        $config = $this->createStub(RateLimiterConfig::class);
        $config->method('canLockout')->willReturn($enabled);
        $config->method('getLockoutWarningThreshold')->willReturn($warningThreshold);
        $config->method('getLockoutSeconds')->willReturn($durationSeconds);

        $requestFingerprint = $this->createStub(RequestFingerprint::class);
        $requestFingerprint->method('resolve')->willReturn($fingerprint);

        return new RateLimitLockout($storage, $dateTime, $config, $requestFingerprint);
    }
}
