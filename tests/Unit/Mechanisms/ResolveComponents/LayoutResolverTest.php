<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms\ResolveComponents;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutResolver;
use Magewirephp\Magewire\Tests\Unit\Fixtures\AttributedComponent;
use Magewirephp\Magewire\Tests\Unit\Fixtures\AttributedComponentInterceptor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

require_once __DIR__ . '/../../Fixtures/AttributedComponent.php';
require_once __DIR__ . '/../../Fixtures/AttributedComponentInterceptor.php';

class LayoutResolverTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $dataStore = new DataStore();
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => $class === DataStore::class ? $dataStore : null);

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    /**
     * Backwards compatibility enabled by default (e.g. by a resolver plugin) gives way to the class attribute,
     * also when a Magento plugin on the component class makes the object manager create an Interceptor.
     */
    public function test_the_backwards_compatibility_attribute_applies_to_an_intercepted_component(): void
    {
        foreach ([new AttributedComponent(), new AttributedComponentInterceptor()] as $component) {
            store($component)->set('magewire:bc', true);

            $this->applyBackwardsCompatibility($component);

            self::assertFalse(store($component)->get('magewire:bc'), $component::class);
        }
    }

    private function applyBackwardsCompatibility(Component $component): void
    {
        // The constructor dependencies include a generated factory, which the unit suite does not have.
        $resolver = ( new ReflectionClass(LayoutResolver::class) )->newInstanceWithoutConstructor();

        ( fn () => $this->handleBackwardsCompatibilityForComponent($component) )->call($resolver);
    }
}
