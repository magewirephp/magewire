<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Controller\Playwright;

use Magento\Framework\App\Router\Base;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Message\ManagerInterface;
use Magewirephp\Magewire\Controller\MagewireDeveloperAction;
use Magewirephp\Magewire\Controller\Playwright\Exceptions;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ExceptionsTest extends TestCase
{
    public function test_production_never_queues_the_complex_test_message(): void
    {
        $state = $this->createMock(ApplicationState::class);
        $state->method('getMode')->willReturn(ApplicationState::MODE_PRODUCTION);
        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects(self::never())->method('addComplexSuccessMessage');

        // The plain unit suite has no generated ForwardFactory. Keep the guard real and mock
        // only the result creation, which belongs to Magento's rendering infrastructure.
        $controller = $this->getMockBuilder(Exceptions::class)->disableOriginalConstructor()->onlyMethods(['forward', 'page'])->getMock();
        $forward = $this->createMock(Forward::class);
        $controller->expects(self::once())->method('forward')->with(Base::NO_ROUTE)->willReturn($forward);
        $controller->expects(self::never())->method('page');

        ( new ReflectionProperty(MagewireDeveloperAction::class, 'applicationState') )->setValue($controller, $state);
        ( new ReflectionProperty(Exceptions::class, 'messageManager') )->setValue($controller, $messageManager);

        self::assertSame($forward, $controller->execute());
    }
}
