<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportLazyLoading;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exceptions\MethodNotFoundException;
use Magewirephp\Magewire\Features\SupportLazyLoading\SupportLazyLoading;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;
use Magewirephp\Magewire\Support\DataCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class SupportLazyLoadingTest extends TestCase
{
    private ?ObjectManagerInterface $previousObjectManager = null;

    protected function setUp(): void
    {
        try {
            $this->previousObjectManager = ObjectManager::getInstance();
        } catch (\RuntimeException) {
            // The unit suite does not need a Magento application.
        }

        $dataStore = new DataStore();
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(
            static fn ($class) => $class === DataStore::class ? $dataStore : null
        );

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(ObjectManager::class, '_instance'))
            ->setValue(null, $this->previousObjectManager);
    }

    #[DataProvider('nonLazyMemos')]
    public function test_lazy_load_is_refused_without_a_placeholder(array $memo): void
    {
        $hook = new SupportLazyLoading();
        $hook->setComponent(new class extends Component {});
        $hook->hydrate($memo);

        $this->expectException(MethodNotFoundException::class);
        $hook->call('__lazyLoad', [], static function (): void {});
    }

    public static function nonLazyMemos(): array
    {
        return [
            'missing memo' => [[]],
            'already loaded' => [['lazyLoaded' => true]],
        ];
    }

    public function test_lazy_placeholder_can_load(): void
    {
        $mountArguments = $this->createMock(DataCollection::class);
        $mountArguments->method('all')->willReturn(['probe' => 'ok']);

        $arguments = $this->createMock(MagewireArguments::class);
        $arguments->method('forMount')->willReturn($mountArguments);

        $resolver = $this->createMock(ComponentResolver::class);
        $resolver->method('arguments')->willReturn($arguments);

        $component = new class extends Component {};
        $component->magewireResolver($resolver);

        $hook = new class extends SupportLazyLoading {
            public array $mountArguments = [];

            public function callMountLifecycleMethod($params)
            {
                $this->mountArguments = $params;
            }
        };
        $hook->setComponent($component);
        $hook->hydrate(['lazyLoaded' => false]);
        $returnedEarly = false;

        $hook->call('__lazyLoad', [], static function () use (&$returnedEarly): void {
            $returnedEarly = true;
        });

        self::assertSame(['probe' => 'ok'], $hook->mountArguments);
        self::assertTrue($returnedEarly);
    }
}
