<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magento\Framework\Interception\Interceptor;
use Magento\Framework\Interception\InterceptorInterface;

class ActionInterceptor extends ActionComponent implements InterceptorInterface
{
    use Interceptor;

    public function tap($callback): static
    {
        return parent::tap($callback);
    }
}
