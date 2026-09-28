<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportEvents;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Component\Form;
use Magewirephp\Magewire\Config;
use Magewirephp\Magewire\EventBus;
use Magewirephp\Magewire\Exceptions\MethodNotFoundException;
use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents;
use Magewirephp\Magewire\Wrapped;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Interception\Interceptor;
use Magento\Framework\Interception\InterceptorInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Reflection\MethodsMap;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator;
use ReflectionMethod;
use ReflectionProperty;

class SupportEventsTest extends TestCase
{
    private ?ObjectManagerInterface $previousObjectManager = null;

    private EventBus $eventBus;

    protected function setUp(): void
    {
        try {
            $this->previousObjectManager = ObjectManager::getInstance();
        } catch (\RuntimeException) {
            // The unit suite does not need a Magento application.
        }

        $methodsMap = $this->createMock(MethodsMap::class);
        $methodsMap->method('getMethodParams')->willReturnCallback(
            static fn ($class, $method) => array_map(
                static fn ($parameter) => ['name' => $parameter->getName()],
                (new ReflectionMethod($class, $method))->getParameters()
            )
        );

        $dataStore = new DataStore();
        $this->eventBus = new EventBus();
        $config = $this->createMock(Config::class);
        $config->method('getValue')->willReturn(false);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(
            fn ($class) => match ($class) {
                DataStore::class => $dataStore,
                EventBus::class => $this->eventBus,
                Config::class => $config,
                default => null,
            }
        );
        $objectManager->method('create')->willReturnCallback(
            static fn ($class, $arguments) => new Wrapped($arguments['target'], $methodsMap)
        );

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(ObjectManager::class, '_instance'))
            ->setValue(null, $this->previousObjectManager);
    }

    public function test_dispatch_refuses_framework_method_even_when_listener_map_names_it(): void
    {
        $component = new class extends Component {
            protected $listeners = ['probe:evt' => 'tap'];

            protected function getListeners(): array
            {
                return $this->listeners;
            }
        };

        $hook = new SupportEvents();
        $hook->setComponent($component);

        $this->expectException(MethodNotFoundException::class);
        $hook->call('__dispatch', ['probe:evt', ['is_object']], static function (): void {});
    }

    public function test_dispatch_allows_application_listener(): void
    {
        $component = new class extends Component {
            protected $listeners = ['probe:evt' => 'onProbe'];

            protected function getListeners(): array
            {
                return $this->listeners;
            }

            public function onProbe(string $value): string
            {
                return $value;
            }
        };

        $hook = new SupportEvents();
        $hook->setComponent($component);
        $result = null;

        $hook->call('__dispatch', ['probe:evt', ['worked']], static function ($value) use (&$result): void {
            $result = $value;
        });

        self::assertSame('worked', $result);
    }

    public function test_dispatch_keeps_undeclared_refresh_listener_as_no_op(): void
    {
        foreach (['refresh', '$refresh'] as $method) {
            $component = new class($method) extends Component {
                protected $listeners;

                public function __construct(string $method)
                {
                    $this->listeners = ['probe:evt' => $method];
                }

                protected function getListeners(): array
                {
                    return $this->listeners;
                }
            };

            $hook = new SupportEvents();
            $hook->setComponent($component);
            $result = 'unchanged';

            $hook->call('__dispatch', ['probe:evt', []], static function ($value) use (&$result): void {
                $result = $value;
            });

            self::assertNull($result);
        }
    }

    public function test_dispatch_refuses_form_method(): void
    {
        $component = new class(new Validator()) extends Form {
            protected $listeners = ['probe:evt' => 'validateOnly'];

            protected function getListeners(): array
            {
                return $this->listeners;
            }
        };

        $hook = new SupportEvents();
        $hook->setComponent($component);

        $this->expectException(MethodNotFoundException::class);
        $hook->call('__dispatch', ['probe:evt', [[]]], static function (): void {});
    }

    public function test_dispatch_refuses_application_lifecycle_method(): void
    {
        $component = new class extends Component {
            protected $listeners = ['probe:evt' => 'boot'];

            protected function getListeners(): array
            {
                return $this->listeners;
            }

            public function boot(): void
            {
            }
        };

        $hook = new SupportEvents();
        $hook->setComponent($component);

        $this->expectException(MethodNotFoundException::class);
        $hook->call('__dispatch', ['probe:evt', []], static function (): void {});
    }

    public function test_dispatch_refuses_magento_interceptor_method(): void
    {
        $component = new class extends Component implements InterceptorInterface {
            use Interceptor;

            protected $listeners = ['probe:evt' => '___callParent'];

            protected function getListeners(): array
            {
                return $this->listeners;
            }
        };

        $hook = new SupportEvents();
        $hook->setComponent($component);

        $this->expectException(MethodNotFoundException::class);
        $hook->call('__dispatch', ['probe:evt', ['getListeners', []]], static function (): void {});
    }

    public function test_call_methods_blocks_dispatch_to_base_method(): void
    {
        $component = new class extends Component {
            protected $listeners = ['probe:evt' => 'onProbe'];

            public bool $listenerUpdated = false;

            protected function getListeners(): array
            {
                return $this->listeners;
            }

            public function setListener(string $method): void
            {
                $this->listeners['probe:evt'] = $method;
                $this->listenerUpdated = true;
            }

            public function onProbe(): void
            {
            }
        };

        $hook = new SupportEvents();
        $hook->setComponent($component);
        $this->eventBus->on('call', static function ($root, $method, $params, $context, $returnEarly) use ($hook): void {
            $hook->call($method, $params, $returnEarly);
        });

        try {
            $this->callComponentMethods($component, [
                ['method' => 'setListener', 'params' => ['tap']],
                ['method' => '__dispatch', 'params' => ['probe:evt', ['is_object']]],
            ]);
            self::fail('Dispatch to tap was accepted');
        } catch (MethodNotFoundException $exception) {
            self::assertTrue($component->listenerUpdated);
            self::assertStringContainsString('[tap]', $exception->getMessage());
        }
    }

    public function test_call_methods_refuses_direct_base_method(): void
    {
        $this->expectException(MethodNotFoundException::class);
        $this->callComponentMethods(new class extends Component {}, [
            ['method' => 'tap', 'params' => ['is_object']],
        ]);
    }

    private function callComponentMethods(Component $component, array $calls): void
    {
        $handler = new class extends HandleComponents {
            public function __construct()
            {
            }

            public function runCalls($component, $calls, $context): void
            {
                $this->callMethods($component, $calls, $context);
            }
        };

        $context = new class {
            public function addEffect($name, $value): void
            {
            }
        };

        $handler->runCalls($component, $calls, $context);
    }
}
