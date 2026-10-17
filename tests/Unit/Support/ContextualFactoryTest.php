<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use ArrayIterator;
use ArrayObject;
use Countable;
use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManager\Config\Compiled as CompiledConfig;
use Magento\Framework\ObjectManager\Config\Config as RuntimeConfig;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManager\Factory\Compiled as CompiledFactory;
use Magento\Framework\ObjectManager\Factory\Dynamic\Developer as DeveloperFactory;
use Magento\Framework\ObjectManager\ObjectManager as NativeObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Support\ContextualFactory;
use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ContextualFactoryConsumer;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ContextualFactoryConsumerInterceptor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use UnexpectedValueException;

require_once __DIR__ . '/../Fixtures/ContextualFactoryConsumer.php';
require_once __DIR__ . '/../Fixtures/ContextualFactoryConsumerInterceptor.php';

class ContextualFactoryTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);

        parent::tearDown();
    }

    public function test_it_reuses_a_private_readonly_dependency_through_an_interceptor(): void
    {
        $service = new ArrayObject(['injected']);
        $consumer = new ContextualFactoryConsumerInterceptor($service, new ArrayObject());
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::never())->method('getArguments');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');
        $objectManager->expects(self::never())->method('create');

        self::assertSame($service, ( new ContextualFactory($consumer, $objectManager, $config) )->dependency('service', Countable::class));
        self::assertSame($service, ( new ContextualFactory($consumer, $objectManager, $config) )->get('service', Countable::class));
    }

    public function test_an_initialized_null_does_not_trigger_fallback(): void
    {
        $consumer = new class {
            private ArrayObject|null $service = null;
        };
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::never())->method('getArguments');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');

        self::assertNull(( new ContextualFactory($consumer, $objectManager, $config) )->dependency('service', Countable::class));
        self::assertNull(( new ContextualFactory($consumer, $objectManager, $config) )->get('service', Countable::class));
    }

    #[DataProvider('argumentMetadata')]
    public function test_uninitialized_properties_fall_back_to_configured_objects(array $argument, bool $shared, bool $usesTypeSharing): void
    {
        $consumer = new class {
            private ArrayObject $service;
        };
        $service = new ArrayObject(['configured']);
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())->method('getArguments')->with($consumer::class)->willReturn(['service' => $argument]);
        $config
            ->expects($usesTypeSharing ? self::once() : self::never())
            ->method('isShared')
            ->with('context.service')
            ->willReturn($shared);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager
            ->expects(self::once())
            ->method($shared ? 'get' : 'create')
            ->with('context.service')
            ->willReturn($service);
        $objectManager->expects(self::never())->method($shared ? 'create' : 'get');

        self::assertSame($service, ( new ContextualFactory($consumer, $objectManager, $config) )->dependency('service', Countable::class));
        self::assertFalse(( new ReflectionProperty($consumer, 'service') )->isInitialized($consumer));
    }

    public static function argumentMetadata(): iterable
    {
        yield 'runtime shared argument' => [['instance' => 'context.service', 'shared' => true], true, false];
        yield 'runtime non-shared argument' => [['instance' => 'context.service', 'shared' => false], false, false];
        yield 'runtime shared type' => [['instance' => 'context.service'], true, true];
        yield 'runtime non-shared type' => [['instance' => 'context.service'], false, true];
        yield 'compiled shared argument' => [['_i_' => 'context.service'], true, false];
        yield 'compiled non-shared argument' => [['_ins_' => 'context.service'], false, false];
    }

    #[DataProvider('explicitResolutionModes')]
    public function test_explicit_methods_override_configured_sharing(array $argument, string $method): void
    {
        $service = new ArrayObject(['explicit']);
        $parameters = ['array' => ['source' => 'explicit']];
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getArguments')->willReturn(['service' => $argument]);
        $config->expects(self::never())->method('isShared');
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $expectation = $objectManager->expects(self::once())->method($method);

        if ($method === 'create') {
            $expectation->with('context.service', $parameters);
        } else {
            $expectation->with('context.service');
        }

        $expectation->willReturn($service);
        $objectManager->expects(self::never())->method($method === 'create' ? 'get' : 'create');
        $factory = new ContextualFactory(new stdClass(), $objectManager, $config);
        $resolved = $method === 'create' ? $factory->create('service', Countable::class, $parameters) : $factory->get('service', Countable::class);

        self::assertSame($service, $resolved);
    }

    public static function explicitResolutionModes(): iterable
    {
        foreach (self::argumentMetadata() as $name => [$argument]) {
            yield $name . ' via get' => [$argument, 'get'];
            yield $name . ' via create' => [$argument, 'create'];
        }
    }

    public function test_creation_bypasses_an_incompatible_stored_property_without_replacing_it(): void
    {
        $consumer = new class {
            public string $service = 'keep me';
        };
        $service = new ArrayObject(['created']);
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getArguments')->willReturn([]);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');
        $objectManager->expects(self::once())->method('create')->with(Countable::class, [])->willReturn($service);

        self::assertSame($service, ( new ContextualFactory($consumer, $objectManager, $config) )->create('service', Countable::class));
        self::assertSame('keep me', $consumer->service);
    }

    #[DataProvider('literalBindings')]
    public function test_literal_bindings_can_be_reused_but_cannot_be_created(mixed $argument): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getArguments')->willReturn(['service' => $argument]);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');
        $objectManager->expects(self::never())->method('create');
        $factory = new ContextualFactory(new stdClass(), $objectManager, $config);
        $expected = is_array($argument) ? $argument['_v_'] ?? null : $argument;

        self::assertSame($expected, $factory->get('service', Countable::class));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('no configured DI type name to create');

        $factory->create('service', Countable::class);
    }

    public static function literalBindings(): iterable
    {
        yield 'runtime null' => [null];
        yield 'compiled null' => [['_vn_' => true]];
        yield 'runtime object' => [new ArrayObject(['literal'])];
        yield 'compiled literal object' => [['_v_' => new ArrayObject(['literal'])]];
    }

    public function test_it_uses_the_original_consumer_type_for_interceptor_config(): void
    {
        $consumer = new ContextualFactoryConsumerInterceptor(new ArrayObject(), new ArrayObject());
        $service = new ArrayObject(['configured']);
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('getArguments')
            ->with(ContextualFactoryConsumer::class)
            ->willReturn(['unstored' => ['_i_' => 'context.service']]);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('get')->with('context.service')->willReturn($service);

        self::assertSame($service, ( new ContextualFactory($consumer, $objectManager, $config) )->dependency('unstored', Countable::class));
    }

    public function test_a_missing_property_and_argument_use_the_explicit_fallback_type(): void
    {
        $consumer = new stdClass();
        $service = new ArrayObject(['fallback']);
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getArguments')->willReturn(null);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::once())->method('get')->with(Countable::class)->willReturn($service);

        self::assertSame($service, ( new ContextualFactory($consumer, $objectManager, $config) )->dependency('missing', Countable::class));
        self::assertFalse(property_exists($consumer, 'missing'));
    }

    #[DataProvider('nullMetadata')]
    public function test_configured_null_is_preserved(mixed $argument): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getArguments')->willReturn(['service' => $argument]);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');

        self::assertNull(( new ContextualFactory(new stdClass(), $objectManager, $config) )->dependency('service', Countable::class));
    }

    public static function nullMetadata(): iterable
    {
        yield 'runtime' => [null];
        yield 'compiled' => [['_vn_' => true]];
    }

    public function test_a_stored_value_with_the_wrong_type_is_not_silently_replaced(): void
    {
        $consumer = new class {
            private string $service = 'invalid';
        };
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must implement or extend');

        ( new ContextualFactory($consumer, $objectManager, $this->createMock(ConfigInterface::class)) )->dependency('service', Countable::class);
    }

    public function test_unsupported_argument_metadata_is_not_silently_ignored(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getArguments')->willReturn(['service' => ['_vac_' => []]]);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('not configured as an object or null');

        ( new ContextualFactory(new stdClass(), $objectManager, $config) )->dependency('service', Countable::class);
    }

    public function test_it_requires_an_explicit_name_and_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ( new ContextualFactory(new stdClass(), $this->createMock(ObjectManagerInterface::class), $this->createMock(ConfigInterface::class)) )->dependency('service', '');
    }

    #[DataProvider('containerModes')]
    public function test_it_resolves_real_magento_preferences_and_virtual_types(bool $compiled): void
    {
        $objectManager = $this->bootObjectManager($compiled);
        $consumer = $objectManager->create(ContextualFactoryConsumer::class);
        $contextual = Factory::for($consumer);

        self::assertSame($objectManager->get('context.service'), $contextual->dependency('service', Countable::class));
        self::assertSame($objectManager->get('context.service'), $contextual->dependency('unstored', Countable::class));
        self::assertSame('context', $contextual->dependency('unstored', Countable::class)['source']);
        self::assertSame($objectManager->get(Countable::class), $contextual->dependency('missing', Countable::class));
        self::assertSame('default', $contextual->dependency('missing', Countable::class)['source']);
        self::assertNotSame($contextual->dependency('service', Countable::class), $contextual->dependency('missing', Countable::class));
    }

    #[DataProvider('containerModes')]
    public function test_explicit_virtual_consumer_context_preserves_transient_arguments(bool $compiled): void
    {
        $objectManager = $this->bootObjectManager($compiled);
        $consumer = $objectManager->create('special.consumer');
        $contextual = Factory::for($consumer, consumerType: 'special.consumer');
        $stored = $contextual->dependency('service', Countable::class);

        self::assertSame($stored, $contextual->dependency('service', Countable::class));
        self::assertSame('transient', $stored['source']);
        self::assertSame('transient', $contextual->dependency('unstored', Countable::class)['source']);
        self::assertNotSame($contextual->dependency('unstored', Countable::class), $contextual->dependency('unstored', Countable::class));
        self::assertNotSame($stored, $contextual->dependency('unstored', Countable::class));
    }

    #[DataProvider('containerModes')]
    public function test_get_reuses_stored_dependencies_and_forces_shared_configured_resolution(bool $compiled): void
    {
        $objectManager = $this->bootObjectManager($compiled);
        $consumer = $objectManager->create('special.consumer');
        $contextual = Factory::for($consumer, consumerType: 'special.consumer');
        $injected = $contextual->dependency('service', Countable::class);
        $shared = $contextual->get('unstored', Countable::class);

        self::assertSame($injected, $contextual->get('service', Countable::class));
        self::assertSame($shared, $contextual->get('unstored', Countable::class));
        self::assertSame($objectManager->get('transient.service'), $shared);
        self::assertSame('transient', $shared['source']);
        self::assertNotSame($shared, $contextual->dependency('unstored', Countable::class));
        self::assertSame($objectManager->get(Countable::class), $contextual->get('missing', Countable::class));
    }

    #[DataProvider('containerModes')]
    public function test_create_preserves_virtual_type_configuration_and_forwards_constructor_arguments(bool $compiled): void
    {
        $objectManager = $this->bootObjectManager($compiled);
        $consumer = $objectManager->create(ContextualFactoryConsumer::class);
        $contextual = Factory::for($consumer);
        $injected = $contextual->get('service', Countable::class);
        $fresh = $contextual->create('service', Countable::class);
        $custom = $contextual->create('service', Countable::class, ['array' => ['source' => 'custom']]);

        self::assertNotSame($injected, $fresh);
        self::assertNotSame($fresh, $contextual->create('service', Countable::class));
        self::assertSame('context', $fresh['source']);
        self::assertSame('custom', $custom['source']);
        self::assertSame($injected, $contextual->get('service', Countable::class));
        self::assertSame('context', $injected['source']);
        self::assertNotSame($objectManager->get(Countable::class), $contextual->create('missing', Countable::class));
        self::assertSame('fallback', $contextual->create('missing', Countable::class, ['array' => ['source' => 'fallback']])['source']);
    }

    public static function containerModes(): iterable
    {
        yield 'developer factory' => [false];
        yield 'compiled factory' => [true];
    }

    private function bootObjectManager(bool $compiled): ObjectManagerInterface
    {
        $config = $compiled ? $this->compiledConfig() : $this->runtimeConfig();
        $sharedInstances = [ConfigInterface::class => $config];
        $factory = $compiled ? new CompiledFactory($config, $sharedInstances) : new DeveloperFactory($config);
        $objectManager = new NativeObjectManager($factory, $config, $sharedInstances);
        $factory->setObjectManager($objectManager);
        ObjectManager::setInstance($objectManager);

        return $objectManager;
    }

    private function runtimeConfig(): ConfigInterface
    {
        $config = new RuntimeConfig();
        $config->extend([
            'preferences' => [Countable::class => ArrayObject::class],
            ArrayObject::class => ['arguments' => ['array' => ['source' => 'default']]],
            'context.service' => ['type' => ArrayObject::class, 'arguments' => ['array' => ['source' => 'context']]],
            'transient.service' => ['type' => ArrayObject::class, 'arguments' => ['array' => ['source' => 'transient']]],
            ContextualFactoryConsumer::class => [
                'arguments' => [
                    'service' => ['instance' => 'context.service'],
                    'unstored' => ['instance' => 'context.service']
                ]
            ],
            'special.consumer' => [
                'type' => ContextualFactoryConsumer::class,
                'arguments' => [
                    'service' => ['instance' => 'transient.service', 'shared' => false],
                    'unstored' => ['instance' => 'transient.service', 'shared' => false]
                ]
            ]
        ]);

        return $config;
    }

    private function compiledConfig(): ConfigInterface
    {
        $arguments = [];

        foreach ([ArrayObject::class => 'default', 'context.service' => 'context', 'transient.service' => 'transient'] as $type => $source) {
            $arguments[$type] = [
                'array' => ['_vac_' => ['source' => $source]],
                'flags' => ['_v_' => 0],
                'iteratorClass' => ['_v_' => ArrayIterator::class]
            ];
        }

        $arguments[ContextualFactoryConsumer::class] = ['service' => ['_i_' => 'context.service'], 'unstored' => ['_i_' => 'context.service']];
        $arguments['special.consumer'] = ['service' => ['_ins_' => 'transient.service'], 'unstored' => ['_ins_' => 'transient.service']];

        return new CompiledConfig([
            'preferences' => [Countable::class => ArrayObject::class],
            'instanceTypes' => ['context.service' => ArrayObject::class, 'transient.service' => ArrayObject::class, 'special.consumer' => ContextualFactoryConsumer::class],
            'arguments' => $arguments
        ]);
    }
}
