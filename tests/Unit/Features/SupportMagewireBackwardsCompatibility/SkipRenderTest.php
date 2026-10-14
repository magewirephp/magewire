<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireBackwardsCompatibility;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\EventBus;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\SupportMagewireBackwardsCompatibility;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

class SkipRenderTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $services = [EventBus::class => new EventBus(), DataStore::class => new DataStore()];
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => $services[$class] ?? null);

        ObjectManager::setInstance($objectManager);

        ( new SupportMagewireBackwardsCompatibility() )->provide();
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public static function skipRenderFalseCalls(): array
    {
        return [
            'after skipRender()' => [true],
            'without an earlier skipRender()' => [false]
        ];
    }

    #[DataProvider('skipRenderFalseCalls')]
    public function test_v1_skip_render_false_takes_back_an_earlier_skip_render(bool $skippedBefore): void
    {
        $component = $this->component(true);

        $skippedBefore && $component->skipRender();
        $component->skipRender(false);

        self::assertFalse(store($component)->has('skipRender'));
        self::assertSame('rendered', $this->render($component));
    }

    public function test_v1_skip_render_skips_rendering(): void
    {
        $component = $this->component(true);

        $component->skipRender();

        self::assertTrue(store($component)->get('skipRender'));
        self::assertNotSame('rendered', $this->render($component));
    }

    public static function componentsWithoutBackwardsCompatibility(): array
    {
        return [
            'unmarked, after skipRender()' => [null, true],
            'unmarked, without an earlier skipRender()' => [null, false],
            'explicitly opted out, after skipRender()' => [false, true],
            'explicitly opted out, without an earlier skipRender()' => [false, false]
        ];
    }

    #[DataProvider('componentsWithoutBackwardsCompatibility')]
    public function test_skip_render_false_skips_rendering_without_backwards_compatibility(
        bool|null $bc,
        bool $skippedBefore
    ): void {
        $component = $this->component($bc);

        $skippedBefore && $component->skipRender();
        $component->skipRender(false);

        self::assertTrue(store($component)->get('skipRender'));
        self::assertNotSame('rendered', $this->render($component));
    }

    /**
     * Returns a component with the given resolved backwards compatibility flag; null leaves it unset.
     */
    private function component(bool|null $bc): Component
    {
        $component = new class extends Component {};

        if ($bc !== null) {
            store($component)->set('magewire:bc', $bc);
        }

        return $component;
    }

    /**
     * Runs the component through HandleComponents::render() with the HTML its block rendered. Returns 'rendered'
     * when the render path runs the render hooks, otherwise what render() returns for a skipped component.
     */
    private function render(Component $component): string
    {
        $handler = new class extends HandleComponents {
            public function __construct()
            {
            }

            public function renderComponent(Component $component, string $html): string
            {
                return $this->render($component, '', $html);
            }

            protected function getView($component)
            {
                return [null, []];
            }

            protected function trackInRenderStack($component, $callback)
            {
                return 'rendered';
            }
        };

        return $handler->renderComponent($component, '<div>block</div>');
    }
}
