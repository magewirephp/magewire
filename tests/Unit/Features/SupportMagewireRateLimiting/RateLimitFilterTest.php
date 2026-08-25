<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireRateLimiting;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Filter\RateLimitFilter;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimiterConfig;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\RateLimitLockout;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\UpdateRequestRateLimiter;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateLimitFilterTest extends TestCase
{
    public function test_an_active_lockout_rejects_before_the_request_budget_is_checked(): void
    {
        $rateLimiter = $this->createMock(UpdateRequestRateLimiter::class);
        $rateLimiter->expects(self::never())->method('validateWithRequestContext');
        $lockout = $this->createMock(RateLimitLockout::class);
        $lockout->method('remainingSeconds')->willReturn(42);

        $filter = $this->createFilter($rateLimiter, $lockout);

        $this->expectException(TooManyRequestsException::class);
        $this->expectExceptionMessage('You have been temporarily locked out due to too many requests. Try again in 42 seconds.');

        $filter->check($this->createStub(RequestContext::class));
    }

    public function test_a_rate_limit_rejection_starts_a_lockout_at_the_configured_threshold(): void
    {
        $rateLimiter = $this->createMock(UpdateRequestRateLimiter::class);
        $rateLimiter->method('validateWithRequestContext')->willReturn(false);
        $lockout = $this->createMock(RateLimitLockout::class);
        $lockout->method('remainingSeconds')->willReturn(0);
        $lockout->expects(self::once())->method('registerRejection')->with(5)->willReturn(60);

        $filter = $this->createFilter($rateLimiter, $lockout);

        $this->expectException(TooManyRequestsException::class);
        $this->expectExceptionMessage('You have been temporarily locked out due to too many requests. Try again in 60 seconds.');

        $filter->check($this->createStub(RequestContext::class));
    }

    public function test_a_rejection_before_the_threshold_keeps_the_regular_warning(): void
    {
        $rateLimiter = $this->createMock(UpdateRequestRateLimiter::class);
        $rateLimiter->method('validateWithRequestContext')->willReturn(false);
        $lockout = $this->createMock(RateLimitLockout::class);
        $lockout->method('remainingSeconds')->willReturn(0);
        $lockout->method('registerRejection')->willReturn(0);

        $filter = $this->createFilter($rateLimiter, $lockout);

        $this->expectException(TooManyRequestsException::class);
        $this->expectExceptionMessage('Too many requests! Please wait.');

        $filter->check($this->createStub(RequestContext::class));
    }

    public function test_component_scope_only_checks_for_an_existing_origin_lockout(): void
    {
        $rateLimiter = $this->createMock(UpdateRequestRateLimiter::class);
        $rateLimiter->expects(self::never())->method('validateWithRequestContext');
        $lockout = $this->createMock(RateLimitLockout::class);
        $lockout->method('remainingSeconds')->willReturn(0);
        $config = $this->createStub(RateLimiterConfig::class);
        $config->method('canRateLimit')->willReturn(true);
        $config->method('canRateLimitRequests')->willReturn(false);

        $filter = $this->createFilter($rateLimiter, $lockout, $config);

        self::assertNull($filter->check($this->createStub(RequestContext::class)));
    }

    private function createFilter(
        MockObject $rateLimiter,
        MockObject $lockout,
        RateLimiterConfig|null $config = null
    ): RateLimitFilter {
        if ($config === null) {
            $config = $this->createStub(RateLimiterConfig::class);
            $config->method('canRateLimit')->willReturn(true);
            $config->method('canRateLimitRequests')->willReturn(true);
            $config->method('getRequestsDecaySeconds')->willReturn(5);
        }

        $applicationState = $this->createStub(ApplicationState::class);
        $applicationState->method('getMode')->willReturn(ApplicationState::MODE_PRODUCTION);

        return new RateLimitFilter($rateLimiter, $lockout, $config, $applicationState);
    }
}
