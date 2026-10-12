<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\MagewireBc;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Model\Concern\BrowserEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class DispatchBrowserEventTest extends TestCase
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
     * Compatibility modules receive the original payload through getBrowserEvents().
     */
    public static function payloads(): array
    {
        return [
            'no data' => [null, 'null'],
            'named data' => [['id' => 5], '{"id":5}'],
            'list data' => [['a', 'b'], '["a","b"]'],
            'object data' => [(object) ['id' => 5], '{"id":5}'],
            'scalar data' => ['saved', '"saved"']
        ];
    }

    #[DataProvider('payloads')]
    public function test_dispatch_browser_event_queues_the_original_payload(mixed $data, string $expected): void
    {
        $component = new class extends Component {};

        $component->dispatchBrowserEvent('item-saved', $data);

        self::assertSame('[{"event":"item-saved","data":' . $expected . '}]', json_encode($component->getBrowserEvents()));
        self::assertSame([], ( new SupportEvents() )->getServerDispatchedEvents($component));
    }

    public function test_dispatch_keeps_the_livewire_event_and_adds_no_browser_events(): void
    {
        $component = new class extends Component {};

        $component->dispatch('item-saved', 'saved');

        self::assertSame([], $component->getBrowserEvents());
        self::assertSame('[{"name":"item-saved","params":["saved"]}]', json_encode(( new SupportEvents() )->getServerDispatchedEvents($component)));
    }

    public function test_dispatch_browser_event_accepts_its_default_payload_and_preserves_queue_order(): void
    {
        $component = new class extends Component {};

        $component->dispatchBrowserEvent('first');
        $component->dispatchBrowserEvent('second', 'saved');

        self::assertSame(
            [
                ['event' => 'first', 'data' => null],
                ['event' => 'second', 'data' => 'saved']
            ],
            $component->getBrowserEvents()
        );
    }

    public function test_legacy_browser_event_trait_keeps_existing_imports_working(): void
    {
        $component = new class extends Component {
            use BrowserEvent;
        };

        $component->dispatchBrowserEvent('legacy', 'saved');
        $component->dispatch('modern', 'saved');

        self::assertSame([['event' => 'legacy', 'data' => 'saved']], $component->getBrowserEvents());
        self::assertSame('[{"name":"modern","params":["saved"]}]', json_encode(( new SupportEvents() )->getServerDispatchedEvents($component)));
    }
}
