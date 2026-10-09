<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

use DOMDocument;
use Magento\Framework\App\ObjectManager as GlobalObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Config;
use Magento\Framework\Event\Config\Converter;
use Magento\Framework\Event\Config\Data as EventConfigData;
use Magento\Framework\Event\Config\Reader;
use Magento\Framework\Event\Invoker\InvokerDefault;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Event\ObserverFactory;
use Magento\Framework\ObjectManager\Config\Config as ObjectManagerConfig;
use Magento\Framework\ObjectManager\Definition\Runtime;
use Magento\Framework\ObjectManager\Factory\Dynamic\Developer;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Enums\RequestMode;
use Magewirephp\Magewire\EventBus;
use Magewirephp\Magewire\MagewireManager;
use Magewirephp\Magewire\MagewireServiceProvider;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Snapshot;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\MagewireArguments;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout\LayoutLifecycle;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutLifecycleManager;
use Magewirephp\Magewire\Model\App\ExceptionManager;
use Magewirephp\Magewire\Support\DataCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;

/** @mago-expect lint:too-many-methods */
class LegacyObserverCacheTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;
    private ObjectManager $objectManager;
    private MagewireManager&MockObject $magewireManager;
    private MagewireServiceProvider&MockObject $serviceProvider;
    private LayoutLifecycle $lifecycle;
    private EventBus $eventBus;
    private DataStore $dataStore;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(GlobalObjectManager::class, '_instance') )->getValue();
        $this->magewireManager = $this->createMock(MagewireManager::class);
        $this->serviceProvider = $this->createMock(MagewireServiceProvider::class);
        $this->lifecycle = new LayoutLifecycle();
        $this->eventBus = new EventBus();
        $this->dataStore = new DataStore();

        $lifecycleManager = $this->createMock(LayoutLifecycleManager::class);
        $lifecycleManager->method('forMagewire')->willReturn($this->lifecycle);
        $lifecycleManager->method('target')->with('magewire')->willReturn($this->lifecycle);

        $exceptionManager = $this->createMock(ExceptionManager::class);
        $exceptionManager->expects(self::never())->method('handleWithBlock');

        $sharedInstances = [
            MagewireManager::class => $this->magewireManager,
            MagewireServiceProvider::class => $this->serviceProvider,
            LayoutLifecycleManager::class => $lifecycleManager,
            ExceptionManager::class => $exceptionManager,
            EventBus::class => $this->eventBus,
            DataStore::class => $this->dataStore
        ];
        $config = new ObjectManagerConfig();
        $factory = new Developer($config, definitions: new Runtime());
        $this->objectManager = new ObjectManager($factory, $config, $sharedInstances);
        $factory->setObjectManager($this->objectManager);
        GlobalObjectManager::setInstance($this->objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(GlobalObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    #[DataProvider('cacheVersions')]
    public function test_initial_render_mounts_and_renders_once(string $cacheVersion): void
    {
        $component = new class extends Component {};
        $component->setName('legacy-observer');
        $block = $this->block($component);
        $block->method('getCacheKey')->willReturn('legacy-observer-cache');

        $mountArguments = $this->createMock(DataCollection::class);
        $mountArguments->method('all')->willReturn(['message' => 'initial']);
        $arguments = $this->createMock(MagewireArguments::class);
        $arguments->method('forMount')->willReturn($mountArguments);
        $resolver = $this->createMock(ComponentResolver::class);
        $resolver->method('arguments')->willReturn($arguments);
        $component->magewireResolver($resolver);

        $this->eventBus->on('magewire:component:construct', static fn () => static fn () => $block);
        $this->serviceProvider->expects(self::once())->method('boot')->with(RequestMode::PRECEDING);
        $this->magewireManager->expects(self::once())->method('mount')->with('legacy-observer', ['message' => 'initial'], 'legacy-observer-cache', $block, $component);
        $this->magewireManager->expects(self::never())->method('update');
        $this->magewireManager->expects(self::once())->method('render')->with($block, '<div>initial</div>')->willReturn([$block, '<div wire:id="legacy-observer">initial</div>']);

        $html = $this->renderBlock($cacheVersion, $block, '<div>initial</div>');

        self::assertSame('<div wire:id="legacy-observer">initial</div>', $html);
        self::assertSame($component, $this->lifecycle->componentFor($block));
    }

    #[DataProvider('cacheVersions')]
    public function test_update_forwards_the_snapshot_and_keeps_update_html(string $cacheVersion): void
    {
        $component = new class extends Component {};
        $block = $this->block($component);
        $snapshot = $this->createMock(Snapshot::class);
        $calls = [['method' => 'save', 'params' => []]];
        $updates = ['message' => 'updated'];
        $this->dataStore->set($component, 'magewire:update', new ComponentRequestContext($snapshot, $calls, $updates));

        $this->serviceProvider->expects(self::never())->method('boot');
        $this->magewireManager->expects(self::never())->method('mount');
        $this->magewireManager->expects(self::once())->method('update')->with($snapshot, $updates, $calls, $block);
        $this->magewireManager->expects(self::never())->method('render');

        self::assertSame('<div>updated</div>', $this->renderBlock($cacheVersion, $block, '<div>updated</div>'));
    }

    #[DataProvider('cacheVersions')]
    public function test_ordinary_blocks_keep_their_html(string $cacheVersion): void
    {
        $this->serviceProvider->expects(self::never())->method('boot');
        $this->magewireManager->expects(self::never())->method('mount');
        $this->magewireManager->expects(self::never())->method('update');
        $this->magewireManager->expects(self::never())->method('render');

        self::assertSame('<div>ordinary</div>', $this->renderBlock($cacheVersion, $this->block(), '<div>ordinary</div>'));
    }

    #[DataProvider('cacheVersions')]
    public function test_hyva_generation_preserves_other_extensions_without_adding_magewire(string $cacheVersion): void
    {
        $events = $this->eventManager($cacheVersion);

        foreach ([[], ['extensions' => [['src' => 'vendor/example/theme-module']], 'other' => 'retained']] as $data) {
            $config = new DataObject($data);
            $events->dispatch('hyva_config_generate_before', ['config' => $config]);

            self::assertSame($data, $config->getData());
        }
    }

    public static function cacheVersions(): array
    {
        return [
            'current registrations' => ['current'],
            'warm v1 registrations' => ['legacy'],
            'current global cache with v1 frontend cache' => ['mixed']
        ];
    }

    private function block(Component|null $component = null): AbstractBlock&MockObject
    {
        $block = $this->createMock(AbstractBlock::class);
        $block->method('getNameInLayout')->willReturn('legacy-observer');
        $block->method('getData')->with('magewire')->willReturn($component);

        return $block;
    }

    private function renderBlock(string $cacheVersion, AbstractBlock $block, string $html): string
    {
        $events = $this->eventManager($cacheVersion);
        $transport = new DataObject(['html' => $html]);
        $renderEvents = [];
        $this->eventBus->on('magento:block:render', static function (LayoutLifecycle $lifecycle) use (&$renderEvents): void {
            $renderEvents[] = ['before', $lifecycle->route()];
        });
        $this->eventBus->on('magento:block:rendered', static function (LayoutLifecycle $lifecycle) use (&$renderEvents): void {
            $renderEvents[] = ['after', $lifecycle->route()];
        });

        $events->dispatch('view_block_abstract_to_html_before', ['block' => $block]);
        $events->dispatch('view_block_abstract_to_html_after', ['block' => $block, 'transport' => $transport]);

        self::assertSame(
            [
                ['before', 'legacy-observer'],
                ['after',  '']
            ],
            $renderEvents
        );

        return $transport->getData('html');
    }

    private function eventManager(string $cacheVersion): EventManager
    {
        $current = $this->readEvents(__DIR__ . '/../../src/etc/events.xml');
        $legacy = $this->readEvents(__DIR__ . '/Fixtures/magewire-v1-frontend-events.xml');
        $serializer = new Json();
        $cached = [
            'global::event_config_cache' => $serializer->serialize($cacheVersion === 'legacy' ? [] : $current),
            'frontend::event_config_cache' => $serializer->serialize($cacheVersion === 'current' ? [] : $legacy)
        ];
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects(self::exactly(2))
            ->method('load')
            ->willReturnCallback(static fn (string $id) => $cached[$id]);
        $cache->expects(self::never())->method('save');
        $reader = $this->createMock(Reader::class);
        $reader->expects(self::never())->method('read');
        $scope = $this->createMock(ScopeInterface::class);
        $scope->method('getCurrentScope')->willReturn('frontend');

        $config = new Config(new EventConfigData($reader, $scope, $cache, serializer: $serializer));
        $invoker = new InvokerDefault(new ObserverFactory($this->objectManager), $this->createMock(State::class), new NullLogger());

        return new EventManager($invoker, $config);
    }

    private function readEvents(string $path): array
    {
        $document = new DOMDocument();
        $document->load($path);

        return ( new Converter() )->convert($document);
    }
}
