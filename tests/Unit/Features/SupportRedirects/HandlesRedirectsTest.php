<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportRedirects;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Config;
use Magewirephp\Magewire\Mechanisms\DataStore;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

class HandlesRedirectsTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    private array $urlBuilderCalls = [];

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $dataStore = new DataStore();
        $config = $this->createMock(Config::class);
        $config->method('getValue')->willReturn(null);

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(function ($path, $params) {
            $this->urlBuilderCalls[] = [$path, $params];
            $query = $params['_query'] ?? [];

            return 'https://shop.test/' . $path . '/' . ( $query === [] ? '' : '?' . http_build_query($query) );
        });

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(static fn ($class) => match ($class) {
            DataStore::class => $dataStore,
            Config::class => $config,
            UrlInterface::class => $urlBuilder,
            default => null
        });

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public function test_redirect_to_a_url(): void
    {
        $component = new class extends Component {};

        $component->redirect('/checkout/cart/');

        self::assertSame('/checkout/cart/', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_redirect_using_navigate(): void
    {
        $component = new class extends Component {};

        $component->redirect('/checkout/cart/', true);

        self::assertSame('/checkout/cart/', store($component)->get('redirect'));
        self::assertTrue(store($component)->get('redirectUsingNavigate'));
    }

    public function test_redirect_with_route_params_builds_the_url(): void
    {
        $component = new class extends Component {};

        $component->redirect('checkout/cart', ['_query' => ['reason' => 'stock']]);

        self::assertSame('https://shop.test/checkout/cart/?reason=stock', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([['checkout/cart', ['_query' => ['reason' => 'stock']]]], $this->urlBuilderCalls);
    }

    public function test_redirect_with_null_params_builds_the_url(): void
    {
        $component = new class extends Component {};

        $component->redirect('/checkout/cart', null);

        self::assertSame('https://shop.test/checkout/cart/', store($component)->get('redirect'));
        self::assertSame([['checkout/cart', []]], $this->urlBuilderCalls);
    }

    public function test_redirect_with_route_params_passes_an_explicit_secure_flag(): void
    {
        $component = new class extends Component {};

        $component->redirect('checkout/cart', [], false);

        self::assertSame([['checkout/cart', ['_secure' => false]]], $this->urlBuilderCalls);
    }

    public function test_redirect_to_an_absolute_url_with_params_adds_them_as_query(): void
    {
        $component = new class extends Component {};

        $component->redirect('https://example.com/landing', ['reason' => 'stock']);

        self::assertSame('https://example.com/landing?reason=stock', store($component)->get('redirect'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_redirect_skips_rendering(): void
    {
        $component = new class extends Component {};

        $component->redirect('checkout/cart', []);

        self::assertTrue(store($component)->get('skipRender'));
    }
}
