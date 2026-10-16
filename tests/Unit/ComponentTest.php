<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\EventBus;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\store;

class ComponentTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $services = [DataStore::class => new DataStore(), EventBus::class => new EventBus()];
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => $services[$class] ?? null);
        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public static function skipRenderCalls(): array
    {
        return [
            'no argument' => [[[]], true],
            'null' => [[[null]], true],
            'true' => [[[true]], true],
            'replacement HTML' => [[['<div>placeholder</div>']], '<div>placeholder</div>'],
            'empty string' => [[['']], true],
            'zero' => [[[0]], true],
            'string zero' => [[['0']], true],
            'false alone' => [[[false]], null],
            'false after skipping' => [[[], [false]], null],
            'false after replacement HTML' => [
                [
                    ['<div>placeholder</div>'],
                    [false]
                ],
                null
            ],
            'skip again after false' => [[[], [false], []], true]
        ];
    }

    #[DataProvider('skipRenderCalls')]
    public function test_only_strict_false_cancels_skipping(array $calls, string|bool|null $expected): void
    {
        $component = $this->component();

        foreach ($calls as $arguments) {
            $component->skipRender(...$arguments);
        }

        self::assertSame($expected, store($component)->get('skipRender'));
        self::assertSame($expected !== null, store($component)->has('skipRender'));
        self::assertFalse(store($component)->has('magewire:bc'));
    }

    public static function compatibilityModes(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
            'not initialized' => [null]
        ];
    }

    #[DataProvider('compatibilityModes')]
    public function test_false_restores_render_hooks_regardless_of_compatibility_initialization(bool|null $bc): void
    {
        $component = $this->component();

        if ($bc !== null) {
            store($component)->set('magewire:bc', $bc);
        }

        $hooks = [];
        on('render', static function (Component $rendered) use ($component, &$hooks) {
            self::assertSame($component, $rendered);
            $hooks[] = 'render';

            return static function () use (&$hooks): void {
                $hooks[] = 'rendered';
            };
        });

        $component->pingAndRender();
        $html = $this->render($component, '<div>' . $component->pings . '</div>');

        self::assertSame('<div wire:id="test">1</div>', $html);
        self::assertSame(['render', 'rendered'], $hooks);
        self::assertFalse(store($component)->has('skipRender'));
        self::assertSame($bc, store($component)->get('magewire:bc'));
    }

    public function test_skipping_bypasses_render_hooks_and_replacement_html_replaces_the_block(): void
    {
        $component = $this->component();
        on('render', static function (): void {
            self::fail('Skipped rendering must not run render hooks.');
        });

        $component->skipRender();
        self::assertSame('<div wire:id="test">block</div>', $this->render($component, '<div>block</div>'));

        $component->skipRender('<div>placeholder</div>');
        self::assertSame('<div wire:id="test">placeholder</div>', $this->render($component, '<div>block</div>'));
    }

    public function test_cancellation_does_not_clear_another_components_skip_flag(): void
    {
        $component = $this->component();
        $other = $this->component();
        $component->skipRender();
        $other->skipRender();

        $component->skipRender(false);

        self::assertFalse(store($component)->has('skipRender'));
        self::assertTrue(store($other)->get('skipRender'));
    }

    private function component(): Component
    {
        $component = new class extends Component {
            public int $pings = 0;

            public function pingAndRender(): void
            {
                $this->skipRender();
                $this->skipRender(false);
                $this->pings++;
            }
        };
        $component->setId('test');

        return $component;
    }

    private function render(Component $component, string $html): string
    {
        // Rendering does not use the constructor's generated ComponentContextFactory or checksum.
        $handler = ( new ReflectionClass(HandleComponents::class) )->newInstanceWithoutConstructor();

        return ( fn () => $this->render($component, '', $html) )->call($handler);
    }
}
