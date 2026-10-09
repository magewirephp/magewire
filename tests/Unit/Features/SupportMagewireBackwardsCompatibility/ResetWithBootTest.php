<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireBackwardsCompatibility;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\EventBus;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\SupportMagewireBackwardsCompatibility;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ResetWithBootTest extends TestCase
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

        ( new SupportMagewireBackwardsCompatibility() )->provide();
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public function test_v1_reset_with_boot_takes_the_values_boot_assigns(): void
    {
        $component = new BootingComponent();
        $component->foo = 42;

        $component->reset(['foo'], true);

        self::assertSame(1337, $component->foo);
    }

    public function test_reset_without_boot_takes_the_class_defaults(): void
    {
        $component = new BootingComponent();
        $component->foo = 42;

        $component->reset(['foo']);

        self::assertNull($component->foo);
    }

    public function test_v1_reset_with_boot_runs_booted_after_boot(): void
    {
        BootingComponent::$calls = [];

        ( new BootingComponent() )->reset(['foo'], true);

        self::assertSame(['boot', 'booted'], BootingComponent::$calls);
    }
}

class BootingComponent extends Component
{
    public static array $calls = [];

    public int|null $foo = null;

    public function boot(): void
    {
        self::$calls[] = 'boot';

        if ($this->foo === null) {
            $this->foo = 1337;
        }
    }

    public function booted(): void
    {
        self::$calls[] = 'booted';
    }
}
