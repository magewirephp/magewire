<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magento\Framework\Interception\Interceptor;
use Magento\Framework\Interception\InterceptorInterface;

/**
 * Shaped like the Interceptor Magento generates when a plugin targets AttributedComponent::onPlugged().
 */
class AttributedComponentInterceptor extends AttributedComponent implements InterceptorInterface
{
    use Interceptor;

    public function onPlugged(): void
    {
        parent::onPlugged();
    }
}
