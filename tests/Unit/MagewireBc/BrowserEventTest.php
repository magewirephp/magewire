<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\MagewireBc;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Model\Concern\BrowserEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class BrowserEventTest extends TestCase
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
     * Expected params are the JSON the browser receives, matching Magewire 1's event.detail.
     */
    public static function payloads(): array
    {
        return [
            'no data' => [null, '{}'],
            'named data' => [['id' => 5], '{"id":5}'],
            'list data' => [['a', 'b'], '["a","b"]'],
            'object data' => [(object) ['id' => 5], '{"id":5}'],
            'scalar data' => ['saved', '"saved"']
        ];
    }

    #[DataProvider('payloads')]
    public function test_dispatch_browser_event_keeps_the_v1_payload_shape(mixed $data, string $expected): void
    {
        $component = new class {
            use BrowserEvent;
        };

        $component->dispatchBrowserEvent('item-saved', $data);

        $dispatches = ( new SupportEvents() )->getServerDispatchedEvents($component);

        self::assertSame('[{"name":"item-saved","params":' . $expected . '}]', json_encode($dispatches));
    }
}
