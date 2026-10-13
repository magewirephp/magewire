<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagentoFlashMessages;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagentoFlashMessages\FlashMessage;
use Magewirephp\Magewire\Features\SupportMagentoFlashMessages\FlashMessageType;
use Magewirephp\Magewire\Features\SupportMagentoFlashMessages\MagewireFlashMessages;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MagewireFlashMessagesTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturnCallback(static fn ($class, $arguments = []) => match ($class) {
            FlashMessage::class => new FlashMessage($arguments['message']),
            MagewireFlashMessages::class => new MagewireFlashMessages(),
            default => null
        });

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public function test_make_applies_the_requested_type(): void
    {
        $messages = new MagewireFlashMessages();

        foreach (FlashMessageType::cases() as $type) {
            self::assertSame($type, $messages->make(new Phrase('Message'), $type)->type());
        }
    }

    public function test_make_defaults_to_a_notice(): void
    {
        self::assertSame(FlashMessageType::Notice, ( new MagewireFlashMessages() )->make(new Phrase('Message'))->type());
    }

    public function test_component_dispatches_messages_of_the_requested_type(): void
    {
        $component = new class extends Component {};

        self::assertSame(FlashMessageType::Error, $component->dispatchErrorMessage(new Phrase('Error'))->type());
        self::assertSame(FlashMessageType::Warning, $component->dispatchWarningMessage(new Phrase('Warning'))->type());
        self::assertSame(FlashMessageType::Success, $component->dispatchSuccessMessage(new Phrase('Success'))->type());
        self::assertSame(FlashMessageType::Notice, $component->dispatchNoticeMessage(new Phrase('Notice'))->type());
        self::assertSame(FlashMessageType::Error, $component->dispatchMessage('error', new Phrase('Error'))->type());
    }
}
