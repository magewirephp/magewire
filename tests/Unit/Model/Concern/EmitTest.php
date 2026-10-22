<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Model\Concern;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Mechanisms\DataStore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EmitTest extends TestCase
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

    public static function emits(): array
    {
        return [
            'emit, several arguments' => ['emit', ['pair', 'left', 'right'], ['left', 'right']],
            'emit, positional values' => ['emit', ['positional', 42, true], [42, true]],
            'emit, a single array' => ['emit', ['named', ['foo' => 'bar']], ['foo' => 'bar']],
            'emit, a single value' => ['emit', ['single', 'value'], ['value']],
            'emit, no arguments' => ['emit', ['none'], []],
            'emitUp, several arguments' => ['emitUp', ['pair', 'left', 'right'], ['left', 'right']],
            'emitSelf, several arguments' => ['emitSelf', ['pair', 'left', 'right'], ['left', 'right']],
            'emitTo, several arguments' => ['emitTo', ['target', 'pair', 'left', 'right'], ['left', 'right']],
            'emitTo, a single array' => ['emitTo', ['target', 'named', ['foo' => 'bar']], ['foo' => 'bar']],
            'emitToRefresh, several arguments' => ['emitToRefresh', ['target', 'left', 'right'], ['left', 'right']],
            'emitToRefreshUp, several arguments' => ['emitToRefreshUp', ['left', 'right'], ['left', 'right']],
            'emitToRefreshUp, no arguments' => ['emitToRefreshUp', [], []],
            'emit, an explicit null' => ['emit', ['nullable', null], [null]],
            'emitUp, an explicit null' => ['emitUp', ['nullable', null], [null]],
            'emitSelf, an explicit null' => ['emitSelf', ['nullable', null], [null]],
            'emitTo, an explicit null' => ['emitTo', ['target', 'nullable', null], [null]],
            'emitToRefresh, an explicit null' => ['emitToRefresh', ['target', null], [null]],
            'emitToRefreshUp, an explicit null' => ['emitToRefreshUp', [null], [null]],
            'emit, null among several arguments' => ['emit', ['pair', null, 'right'], [null, 'right']]
        ];
    }

    #[DataProvider('emits')]
    public function test_emit_passes_the_listener_arguments(string $method, array $arguments, array $expected): void
    {
        $component = new class extends Component {};

        $event = $component->{$method}(...$arguments);

        self::assertSame($expected, $this->params($event));
    }

    private function params(Event $event): array
    {
        return ( fn () => $this->params )->call($event);
    }
}
