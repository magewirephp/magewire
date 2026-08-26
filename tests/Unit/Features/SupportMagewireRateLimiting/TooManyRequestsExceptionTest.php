<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireRateLimiting;

use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use PHPUnit\Framework\TestCase;

class TooManyRequestsExceptionTest extends TestCase
{
    public function test_a_regular_rejection_has_no_additional_response_headers(): void
    {
        self::assertSame([], ( new TooManyRequestsException() )->headers());
    }

    public function test_a_lockout_carries_its_remaining_duration_as_retry_after(): void
    {
        self::assertSame(['Retry-After' => '42'], TooManyRequestsException::forLockout(42)->headers());
    }
}
