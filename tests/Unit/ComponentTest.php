<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\DataStore;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

class ComponentTest extends TestCase
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

    public function test_skip_render_skips_rendering(): void
    {
        $component = new class extends Component {};

        $component->skipRender();

        self::assertTrue(store($component)->get('skipRender'));
    }

    public function test_skip_render_with_html_renders_the_html_instead(): void
    {
        $component = new class extends Component {};

        $component->skipRender('<div>placeholder</div>');

        self::assertSame('<div>placeholder</div>', store($component)->get('skipRender'));
    }

    public function test_skip_render_false_takes_back_an_earlier_skip_render(): void
    {
        $component = new class extends Component {};

        $component->skipRender();
        $component->skipRender(false);

        self::assertFalse(store($component)->has('skipRender'));
        self::assertFalse(store($component)->get('skipRender', false));
    }

    public function test_skip_render_false_without_an_earlier_skip_render_keeps_rendering(): void
    {
        $component = new class extends Component {};

        $component->skipRender(false);

        self::assertFalse(store($component)->has('skipRender'));
    }
}
