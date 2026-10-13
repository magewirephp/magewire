<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\ApplicationContainer;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Mechanisms\ComponentRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ComponentRegistryTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $registry = new ComponentRegistry();
        $container = $this->createMock(ApplicationContainer::class);
        $container->method('make')->willReturnCallback(static fn ($abstract) => $abstract === ComponentRegistry::class ? $registry : null);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => $class === ApplicationContainer::class ? $container : null);

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public function test_a_layout_block_name_is_the_component_name(): void
    {
        self::assertSame('example.panel', ( new ComponentRegistry() )->getName('example.panel'));
    }

    public function test_a_component_is_named_by_its_name(): void
    {
        $component = new class extends Component {};
        $component->setName('example.panel');

        self::assertSame('example.panel', ( new ComponentRegistry() )->getName($component));
    }

    public function test_an_event_targets_a_component_by_its_layout_block_name(): void
    {
        $event = ( new Event('$refresh', []) )->to('example.target');

        self::assertSame(['name' => '$refresh', 'params' => [], 'to' => 'example.target'], $event->serialize());
    }
}
