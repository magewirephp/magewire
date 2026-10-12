<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\MagewireBc;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportEvents\SupportEvents;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\SupportBrowserEvents;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext\Effects;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext\Memo;
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
     * The browser handler turns an empty payload into {} like Magewire 1, so the data travels unchanged.
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
    public function test_dispatch_browser_event_sends_the_v1_payload_in_the_bc_effect(mixed $data, string $expected): void
    {
        $component = new class extends Component {};

        $component->dispatchBrowserEvent('item-saved', $data);

        self::assertSame('[{"event":"item-saved","data":' . $expected . '}]', json_encode($this->dehydrate($component)['dispatches']));
        self::assertSame([], ( new SupportEvents() )->getServerDispatchedEvents($component));
    }

    public function test_dispatch_keeps_the_livewire_event_and_adds_no_bc_dispatches(): void
    {
        $component = new class extends Component {};

        $component->dispatch('item-saved', 'saved');

        self::assertSame([], $this->dehydrate($component));
        self::assertSame('[{"name":"item-saved","params":["saved"]}]', json_encode(( new SupportEvents() )->getServerDispatchedEvents($component)));
    }

    private function dehydrate(Component $component): array
    {
        $context = new ComponentContext(null, $component, false, new Effects(), new Memo());
        $hook = new SupportBrowserEvents();
        $hook->setComponent($component);
        $hook->dehydrate($context);

        return $context->getEffects()->getData('bc', default: []);
    }
}
