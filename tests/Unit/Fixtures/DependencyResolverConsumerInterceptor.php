<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magento\Framework\Interception\Interceptor;
use Magento\Framework\Interception\InterceptorInterface;

class DependencyResolverConsumerInterceptor extends DependencyResolverConsumer implements InterceptorInterface
{
    use Interceptor;
}
