<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireBackwardsCompatibility;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\EventBus;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\SupportMagewireBackwardsCompatibility;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Tests\Unit\Fixtures\BootingComponent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

require_once __DIR__ . '/../../Fixtures/BootingComponent.php';

class ResetWithBootTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $services = [EventBus::class => new EventBus(), DataStore::class => new DataStore()];
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => $services[$class] ?? null);
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
        $component = $this->component(true);
        $component->foo = 42;

        $component->reset(['foo'], true);

        self::assertSame(1337, $component->foo);
    }

    public function test_reset_without_boot_takes_the_class_defaults(): void
    {
        $component = $this->component(true);
        $component->foo = 42;

        $component->reset(['foo']);

        self::assertNull($component->foo);
    }

    public function test_v1_reset_with_boot_runs_booted_after_boot(): void
    {
        $component = $this->component(true);
        BootingComponent::$calls = [];

        $component->reset(['foo'], true);

        self::assertSame(['boot', 'booted'], BootingComponent::$calls);
    }

    public static function componentsWithoutBackwardsCompatibility(): array
    {
        return [
            'unmarked' => [null],
            'explicitly opted out' => [false]
        ];
    }

    #[DataProvider('componentsWithoutBackwardsCompatibility')]
    public function test_reset_with_the_v1_arguments_keeps_the_class_defaults_without_backwards_compatibility(
        bool|null $bc
    ): void {
        $component = $this->component($bc);
        $component->foo = 42;

        $component->reset(['foo'], true);

        self::assertNull($component->foo);
    }

    #[DataProvider('componentsWithoutBackwardsCompatibility')]
    public function test_reset_with_the_v1_arguments_runs_no_lifecycle_hooks_without_backwards_compatibility(
        bool|null $bc
    ): void {
        $component = $this->component($bc);
        BootingComponent::$calls = [];

        $component->reset(['foo'], true);

        self::assertSame([], BootingComponent::$calls);
    }

    /**
     * Returns a component with the given resolved backwards compatibility flag; null leaves it unset.
     */
    private function component(bool|null $bc): BootingComponent
    {
        $component = new BootingComponent();

        if ($bc !== null) {
            store($component)->set('magewire:bc', $bc);
        }

        return $component;
    }
}
