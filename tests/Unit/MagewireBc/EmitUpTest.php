<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\MagewireBc;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\Event;
use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;
use Magewirephp\Magewire\Mechanisms\DataStore;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EmitUpTest extends TestCase
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
     * Compatibility modules receive the events through getAncestorEvents(), in the order they were emitted.
     */
    public function test_emit_up_queues_the_event_apart_from_livewire_dispatches(): void
    {
        $component = new class extends Component {};

        $component->emitUp('child_picked', ['v' => 1]);
        $component->emitUp('child_scalar', 'saved');

        self::assertSame([['name' => 'child_picked', 'params' => ['v' => 1]], ['name' => 'child_scalar', 'params' => ['saved']]], $this->serialize($component->getAncestorEvents()));
        self::assertSame([], ( new SupportEvents() )->getServerDispatchedEvents($component));
    }

    public function test_emit_up_returns_the_queued_event(): void
    {
        $component = new class extends Component {};

        $event = $component->emitUp('child_picked');

        self::assertSame([$event], $component->getAncestorEvents());
    }

    public function test_emit_to_refresh_up_queues_a_refresh(): void
    {
        $component = new class extends Component {};

        $component->emitToRefreshUp();

        self::assertSame([['name' => '$refresh', 'params' => []]], $this->serialize($component->getAncestorEvents()));
        self::assertSame([], ( new SupportEvents() )->getServerDispatchedEvents($component));
    }

    public function test_dispatch_keeps_the_livewire_event_and_queues_nothing(): void
    {
        $component = new class extends Component {};

        $component->dispatch('child_picked', v: 1);

        self::assertSame([], $component->getAncestorEvents());
        self::assertSame([['name' => 'child_picked', 'params' => ['v' => 1]]], ( new SupportEvents() )->getServerDispatchedEvents($component));
    }

    /**
     * @param Event[] $events
     */
    private function serialize(array $events): array
    {
        return array_map(static fn (Event $event) => $event->serialize(), $events);
    }
}
