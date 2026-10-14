<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Concerns;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\EventBus;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\on;

class InteractsWithPropertiesTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $eventBus = new EventBus();
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => $class === EventBus::class ? $eventBus : null);
        $objectManager->method('create')->willReturnCallback(static fn ($class) => new $class());

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public function test_reset_restores_the_class_defaults(): void
    {
        $component = new ResettableComponent();
        $component->foo = 'changed';
        $component->limit = 10;

        $component->reset();

        self::assertNull($component->foo);
        self::assertSame(5, $component->limit);
    }

    public function test_reset_lets_a_listener_prepare_the_fresh_instance(): void
    {
        $received = [];
        $finishedWith = null;

        on('magewire:component:reset', static function ($component, $freshInstance, $properties, $arguments) use (&$received, &$finishedWith) {
            $received = [$component, $properties, $arguments];
            // E.g. the value the layout assigns to the component.
            $freshInstance->limit = 3;

            return static function () use ($component, &$finishedWith): void {
                $finishedWith = $component->limit;
            };
        });

        $component = new ResettableComponent();
        $component->foo = 'changed';
        $component->limit = 10;

        $component->reset(['limit'], true);

        self::assertSame([$component, ['limit'], [['limit'], true]], $received);
        self::assertSame(3, $component->limit);
        self::assertSame('changed', $component->foo);
        self::assertSame(3, $finishedWith);
    }
}

class ResettableComponent extends Component
{
    public string|null $foo = null;

    public int $limit = 5;
}
