<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms;

use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManager\Config\Compiled as CompiledConfig;
use Magento\Framework\ObjectManager\Factory\Compiled as CompiledFactory;
use Magento\Framework\ObjectManager\ObjectManager as NativeObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\ApplicationContainer;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Mechanisms\ComponentRegistry;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ExamplePanel;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ExamplePanelWithDependency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;

require_once __DIR__ . '/../Fixtures/ExamplePanel.php';
require_once __DIR__ . '/../Fixtures/ExamplePanelWithDependency.php';

/** @mago-expect lint:too-many-methods */
class ComponentRegistryTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    private ComponentRegistry $registry;

    private NativeObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $registry = new ComponentRegistry();
        $this->registry = $registry;
        $container = $this->createMock(ApplicationContainer::class);
        $container->method('make')->willReturnCallback(static fn ($abstract) => $abstract === ComponentRegistry::class ? $registry : null);

        $config = new CompiledConfig([]);
        $sharedInstances = [ApplicationContainer::class => $container];
        $factory = new CompiledFactory($config, $sharedInstances);
        $this->objectManager = new NativeObjectManager($factory, $config, $sharedInstances);
        $factory->setObjectManager($this->objectManager);

        ObjectManager::setInstance($this->objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public static function layoutBlockNames(): array
    {
        return [
            'dotted name' => ['example.panel'],
            'uppercase letters' => ['Example.Panel'],
            'initial digit' => ['1example.panel'],
            'hyphens and underscores' => ['example-panel_2'],
            'single letter' => ['a'],
            'single digit' => ['0'],
            'repeated separators' => ['a..__--9'],
            'Unicode decimal digit after the first character' => ['example١']
        ];
    }

    #[DataProvider('layoutBlockNames')]
    public function test_a_layout_block_name_is_the_component_name(string $name): void
    {
        self::assertSame($name, $this->registry->getName($name));
    }

    public static function invalidLayoutBlockNames(): array
    {
        return [
            'empty name' => [''],
            'initial underscore' => ['_example'],
            'initial hyphen' => ['-example'],
            'initial dot' => ['.example'],
            'space' => ['example panel'],
            'forward slash' => ['example/panel'],
            'backslash' => ['example\\panel'],
            'colon' => ['example:panel'],
            'HTML characters' => ['example<panel>'],
            'leading whitespace' => [' example.panel'],
            'trailing whitespace' => ['example.panel '],
            'trailing newline' => ["example.panel\n"],
            'null byte' => ["example\0panel"],
            'Unicode initial digit' => ['١example'],
            'Unicode letter' => ['éxample.panel'],
            'invalid UTF-8' => ["example\xFF"]
        ];
    }

    #[DataProvider('invalidLayoutBlockNames')]
    public function test_an_invalid_unregistered_layout_name_is_rejected(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid layout block name "%s".', $name));

        $this->registry->getName($name);
    }

    public function test_a_component_is_named_by_its_name(): void
    {
        $component = new class extends Component {};
        $component->setName('example.panel');

        self::assertSame('example.panel', ( new ComponentRegistry() )->getName($component));
    }

    public function test_an_assigned_component_name_is_preserved_without_schema_validation(): void
    {
        $component = new ExamplePanel();
        $component->setName('example\\panel_0');

        $event = ( new Event('example.updated', []) )->to($component);

        self::assertSame(['name' => 'example.updated', 'params' => [], 'to' => 'example\\panel_0'], $event->serialize());
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

    public static function componentCreationTypes(): array
    {
        return [
            'constructor dependency' => [ExamplePanelWithDependency::class],
            'Magento preference' => [ExamplePanel::class]
        ];
    }

    #[DataProvider('componentCreationTypes')]
    public function test_new_creates_fresh_components_through_magento_di(string $type): void
    {
        $this->objectManager->configure([
            'preferences' => [ExamplePanel::class => ExamplePanelWithDependency::class],
            'arguments' => [
                ExamplePanelWithDependency::class => ['logger' => ['_i_' => NullLogger::class]]
            ]
        ]);
        $this->registry->component('example.panel', $type);

        $first = $this->registry->new('example.panel', 'first-id');
        $second = $this->registry->new('example.panel', 'second-id');

        self::assertInstanceOf(ExamplePanelWithDependency::class, $first);
        self::assertInstanceOf(ExamplePanelWithDependency::class, $second);
        self::assertNotSame($first, $second);
        self::assertSame($this->objectManager->get(NullLogger::class), $first->logger);
        self::assertSame($first->logger, $second->logger);
        self::assertSame('first-id', $first->getId());
        self::assertSame('second-id', $second->getId());
        self::assertSame('example.panel', $first->getName());
        self::assertSame('example.panel', $second->getName());
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

    public function test_a_registered_alias_is_resolved_before_layout_name_validation(): void
    {
        $this->registry->component('example.primary', ExamplePanel::class);
        $this->registry->component('legacy:panel', ExamplePanel::class);

        $event = ( new Event('example.updated', []) )->to('legacy:panel');

        self::assertSame(['name' => 'example.updated', 'params' => [], 'to' => 'example.primary'], $event->serialize());
    }

    public function test_an_event_cannot_serialize_an_invalid_layout_name(): void
    {
        $event = ( new Event('example.updated', []) )->to('example/panel');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid layout block name "example/panel".');

        $event->serialize();
    }

    public function test_an_empty_event_target_is_rejected_instead_of_broadcast(): void
    {
        $event = ( new Event('example.updated', []) )->to('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid layout block name "".');

        $event->serialize();
    }

    public function test_an_event_without_a_target_remains_a_broadcast(): void
    {
        $event = new Event('example.updated', []);

        self::assertSame(['name' => 'example.updated', 'params' => []], $event->serialize());
        self::assertSame(['name' => 'example.updated', 'params' => [], 'self' => true], $event->self()->serialize());
    }

    #[DataProvider('layoutBlockNames')]
    public function test_an_event_targets_a_component_by_its_layout_block_name(string $name): void
    {
        $event = ( new Event('example.updated', []) )->to($name);

        self::assertSame(['name' => 'example.updated', 'params' => [], 'to' => $name], $event->serialize());
    }

    /** The registry the container hands to Magewire, e.g. to an event that resolves its target. */
    private function registry(): ComponentRegistry
    {
        return $this->registry;
    }
}
