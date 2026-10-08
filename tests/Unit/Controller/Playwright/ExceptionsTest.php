<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Controller\Playwright;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Router\Base;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magewirephp\Magewire\Controller\Playwright\Exceptions;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_production_never_queues_the_complex_test_message(): void
    {
        $pageFactory = $this->createMock(PageFactory::class);
        $pageFactory->expects(self::never())->method('create');

        $forward = $this->createMock(Forward::class);
        $forward->expects(self::once())->method('forward')->with(Base::NO_ROUTE)->willReturnSelf();
        $forwardFactory = $this->createMock(ForwardFactory::class);
        $forwardFactory->method('create')->willReturn($forward);

        $state = $this->createMock(ApplicationState::class);
        $state->method('getMode')->willReturn(ApplicationState::MODE_PRODUCTION);

        $request = $this->createMock(HttpRequest::class);
        $request->expects(self::never())->method('isPost');
        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects(self::never())->method('addComplexSuccessMessage');
        $jsonFactory = $this->createMock(JsonFactory::class);
        $jsonFactory->expects(self::never())->method('create');

        $controller = new Exceptions(
            $pageFactory,
            $forwardFactory,
            $state,
            $request,
            $messageManager,
            $jsonFactory
        );

        self::assertSame($forward, $controller->execute());
    }
}
