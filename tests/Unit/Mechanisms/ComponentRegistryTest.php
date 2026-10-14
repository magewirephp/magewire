<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\ApplicationContainer;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Mechanisms\ComponentRegistry;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ExamplePanel;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

require_once __DIR__ . '/../Fixtures/ExamplePanel.php';

class ComponentRegistryTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    private ComponentRegistry $registry;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $registry = new ComponentRegistry();
        $this->registry = $registry;
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

    public function test_a_registered_alias_resolves_to_the_name_new_assigns(): void
    {
        $registry = $this->registry();
        $registry->component('example.primary', ExamplePanel::class);
        $registry->component('example.secondary', ExamplePanel::class);

        $component = $registry->new('example.secondary', 'example-id');

        self::assertSame('example.primary', $component->getName());
        self::assertSame('example.primary', $registry->getName('example.secondary'));
    }

    public function test_an_event_to_a_registered_alias_targets_the_name_new_assigns(): void
    {
        $registry = $this->registry();
        $registry->component('example.primary', ExamplePanel::class);
        $registry->component('example.secondary', ExamplePanel::class);

        $event = ( new Event('$refresh', []) )->to('example.secondary');

        self::assertSame(['name' => '$refresh', 'params' => [], 'to' => 'example.primary'], $event->serialize());
    }

    public function test_a_registered_alias_of_its_own_class_keeps_its_name(): void
    {
        $registry = $this->registry();
        $registry->component('example.panel', ExamplePanel::class);

        self::assertSame('example.panel', $registry->getName('example.panel'));
    }

    public function test_an_event_targets_a_component_by_its_layout_block_name(): void
    {
        $event = ( new Event('$refresh', []) )->to('example.target');

        self::assertSame(['name' => '$refresh', 'params' => [], 'to' => 'example.target'], $event->serialize());
    }

    /** The registry the container hands to Magewire, e.g. to an event that resolves its target. */
    private function registry(): ComponentRegistry
    {
        return $this->registry;
    }
}
