<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Magento\Framework\App\ObjectManager as GlobalObjectManager;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\ApplicationContainer;
use Magewirephp\Magewire\Containers;
use Magewirephp\Magewire\Exceptions\ContainerEntryNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionProperty;
use stdClass;

use function Magewirephp\Magewire\app;

require_once __DIR__ . '/Fixtures/ApplicationContainerFixture.php';

/** @mago-expect lint:too-many-methods */
class ApplicationContainerTest extends TestCase
{
    public function test_it_uses_magento_get_without_arguments(): void
    {
        $service = new ApplicationContainerFixture('shared');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('get')->with(ApplicationContainerFixture::class)->willReturn($service);

        $container = $this->createContainer($objectManager);

        self::assertSame($service, $container->make(ApplicationContainerFixture::class));
    }

    public function test_it_forwards_arguments_to_a_fresh_magento_build(): void
    {
        $arguments = ['value' => 'contextual'];
        $service = new ApplicationContainerFixture('contextual');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('create')->with(ApplicationContainerFixture::class, $arguments)->willReturn($service);

        $container = $this->createContainer($objectManager);

        self::assertSame($service, $container->make(ApplicationContainerFixture::class, parameters: $arguments));
    }

    public function test_it_resolves_interfaces_through_magento_preferences(): void
    {
        $service = new ApplicationContainerFixture('preferred');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('get')->with(ApplicationContainerFixtureContract::class)->willReturn($service);

        $container = $this->createContainer($objectManager, preferences: [ApplicationContainerFixtureContract::class => ApplicationContainerFixture::class]);

        self::assertSame($service, $container->make(ApplicationContainerFixtureContract::class));
    }

    public function test_it_resolves_magento_virtual_types(): void
    {
        $arguments = ['value' => 'virtual'];
        $service = new ApplicationContainerFixture('virtual');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('create')->with('fixture.virtual', $arguments)->willReturn($service);

        $container = $this->createContainer($objectManager, virtualTypes: ['fixture.virtual' => ApplicationContainerFixture::class]);

        self::assertSame($service, $container->make('fixture.virtual', $arguments));
    }

    public function test_it_preserves_shared_aliases_and_parameterizes_fresh_alias_builds(): void
    {
        $shared = new ApplicationContainerFixture('shared alias');
        $fresh = new ApplicationContainerFixture('fresh alias');
        $arguments = ['value' => 'fresh alias'];
        $containers = $this->createMock(Containers::class);
        $containers->method('has')->with('livewire')->willReturn(true);
        $containers->expects(self::once())->method('item')->with('livewire')->willReturn($shared);
        $containers->expects(self::once())->method('itemType')->with('livewire')->willReturn(ApplicationContainerFixture::class);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('create')->with(ApplicationContainerFixture::class, $arguments)->willReturn($fresh);

        $container = $this->createContainer($objectManager, $containers);

        self::assertSame($shared, $container->make('livewire'));
        self::assertSame($fresh, $container->make('livewire', $arguments));
    }

    public function test_runtime_singletons_are_shared_but_explicit_arguments_are_contextual(): void
    {
        $resolutions = 0;
        $container = $this->createContainer();
        $container->singleton('runtime.service', static function (
            ApplicationContainer $container,
            array $arguments
        ) use (&$resolutions): ApplicationContainerFixture {
            $resolutions++;

            return new ApplicationContainerFixture($arguments['value'] ?? 'shared');
        });

        $shared = $container->make('runtime.service');
        $contextual = $container->make('runtime.service', ['value' => 'contextual']);

        self::assertSame($shared, $container->make('runtime.service'));
        self::assertNotSame($shared, $contextual);
        self::assertSame('contextual', $contextual->value);
        self::assertSame(2, $resolutions);
    }

    public function test_default_singletons_resolve_through_magento_once(): void
    {
        $service = new ApplicationContainerFixture('shared');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('get')->with(ApplicationContainerFixture::class)->willReturn($service);
        $container = $this->createContainer($objectManager);
        $container->singleton(ApplicationContainerFixture::class);

        self::assertSame($service, $container->make(ApplicationContainerFixture::class));
        self::assertSame($service, $container->make(ApplicationContainerFixture::class));
    }

    public function test_runtime_instances_are_returned_exactly(): void
    {
        $instance = new stdClass();
        $container = $this->createContainer();

        self::assertSame($instance, $container->instance('runtime.instance', $instance));
        self::assertTrue($container->bound('runtime.instance'));
        self::assertSame($instance, $container->make('runtime.instance'));
    }

    public function test_has_inspects_all_sources_without_resolving_services(): void
    {
        $containers = $this->createMock(Containers::class);
        $containers->method('has')->willReturnCallback(static fn (string $id): bool => $id === 'livewire');
        $container = $this->createContainer(
            containers: $containers,
            preferences: [ApplicationContainerFixtureContract::class => ApplicationContainerFixture::class],
            virtualTypes: ['fixture.virtual' => ApplicationContainerFixture::class]
        );
        $container->singleton('runtime.singleton', static fn (): stdClass => new stdClass());

        self::assertTrue($container->has(ApplicationContainerFixture::class));
        self::assertFalse($container->bound(ApplicationContainerFixture::class));
        self::assertTrue($container->has(ApplicationContainerFixtureContract::class));
        self::assertTrue($container->bound(ApplicationContainerFixtureContract::class));
        self::assertTrue($container->has('fixture.virtual'));
        self::assertTrue($container->has('livewire'));
        self::assertTrue($container->has('runtime.singleton'));
        self::assertFalse($container->has('session.store'));
    }

    public function test_unknown_targets_have_stable_laravel_and_psr_exceptions(): void
    {
        $container = $this->createContainer();

        try {
            $container->get('missing.service');
            self::fail('A missing PSR entry should throw.');
        } catch (ContainerEntryNotFoundException $exception) {
            self::assertInstanceOf(NotFoundExceptionInterface::class, $exception);
            self::assertStringContainsString('missing.service', $exception->getMessage());
        }

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('missing.service');

        $container->make('missing.service');
    }

    public function test_it_delegates_legacy_object_manager_methods(): void
    {
        $arguments = ['value' => 'created'];
        $configuration = ['preferences' => ['service' => ApplicationContainerFixture::class]];
        $service = new ApplicationContainerFixture('created');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('create')->with(ApplicationContainerFixture::class, $arguments)->willReturn($service);
        $objectManager->expects(self::once())->method('configure')->with($configuration);
        $container = $this->createContainer($objectManager);

        self::assertSame($service, $container->create(ApplicationContainerFixture::class, $arguments));
        $container->configure($configuration);
    }

    public function test_helper_returns_the_adapter_and_forwards_resolution_arguments(): void
    {
        $arguments = ['value' => 'helper'];
        $service = new ApplicationContainerFixture('helper');
        $container = $this->createMock(ApplicationContainer::class);
        $container->expects(self::once())->method('make')->with(ApplicationContainerFixture::class, $arguments)->willReturn($service);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::exactly(2))->method('get')->with(ApplicationContainer::class)->willReturn($container);

        $instanceProperty = new ReflectionProperty(GlobalObjectManager::class, '_instance');
        $previousInstance = $instanceProperty->getValue();
        GlobalObjectManager::setInstance($objectManager);

        try {
            self::assertSame($container, app());
            self::assertSame($service, app(ApplicationContainerFixture::class, $arguments));
        } finally {
            $instanceProperty->setValue(null, $previousInstance);
        }
    }

    /**
     * @param array<string, string> $preferences
     * @param array<string, string> $virtualTypes
     */
    private function createContainer(
        ObjectManagerInterface|null $objectManager = null,
        Containers|null $containers = null,
        array $preferences = [],
        array $virtualTypes = []
    ): ApplicationContainer {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getPreferences')->willReturn($preferences);
        $config->method('getVirtualTypes')->willReturn($virtualTypes);

        return new ApplicationContainer($objectManager ?? $this->createMock(ObjectManagerInterface::class), $config, $containers ?? $this->containersWithoutAliases());
    }

    private function containersWithoutAliases(): Containers&MockObject
    {
        $containers = $this->createMock(Containers::class);
        $containers->method('has')->willReturn(false);

        return $containers;
    }
}
