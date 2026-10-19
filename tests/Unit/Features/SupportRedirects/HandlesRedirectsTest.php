<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportRedirects;

use GuzzleHttp\Psr7\Uri;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Config;
use Magewirephp\Magewire\Mechanisms\DataStore;
use Magewirephp\Magewire\Support\Url;
use Magewirephp\Magewire\Tests\Unit\Fixtures\CreatesUriFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

require_once __DIR__ . '/../../Fixtures/CreatesUriFactory.php';

/** @mago-expect lint:too-many-methods */
class HandlesRedirectsTest extends TestCase
{
    use CreatesUriFactory;

    private ObjectManagerInterface|null $previousObjectManager = null;

    private array $urlBuilderCalls = [];

    private array $configValues = [];

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        $dataStore = new DataStore();
        $config = $this->createMock(Config::class);
        $config->method('getValue')->willReturnCallback(fn ($path) => $this->configValues[$path] ?? null);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnMap([['web/default/front', 'store', null, 'cms']]);

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder
            ->method('getUrl')
            ->willReturnCallback(function ($path, $params) {
                $this->urlBuilderCalls[] = [$path, $params];
                $query = $params['_query'] ?? [];
                $fragment = $params['_fragment'] ?? null;

                return 'https://shop.test/' . trim($path, '/') . '/' . ( $query === [] ? '' : '?' . http_build_query($query) ) . ( $fragment === null ? '' : '#' . $fragment );
            });

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturnCallback(static fn ($class, $arguments) => match (ltrim($class, '\\')) { Uri::class => new Uri($arguments['uri']) });
        $urlSupport = new Url($urlBuilder, $scopeConfig, $this->uriFactory($objectManager));
        $objectManager
            ->method('get')
            ->willReturnCallback(static fn ($class) => match ($class) {
                DataStore::class => $dataStore,
                Config::class => $config,
                UrlInterface::class => $urlBuilder,
                ScopeConfigInterface::class => $scopeConfig,
                Url::class => $urlSupport,
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
        $component = $this->component(null);

        $component->redirect('/checkout/cart/');

        self::assertSame('/checkout/cart/', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertTrue(store($component)->get('skipRender'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_redirect_using_navigate(): void
    {
        $component = $this->component(null);

        $component->redirect('/checkout/cart/', true);

        self::assertSame('/checkout/cart/', store($component)->get('redirect'));
        self::assertTrue(store($component)->get('redirectUsingNavigate'));
    }

    public function test_redirect_route_builds_the_url_of_a_magento_route(): void
    {
        $component = $this->component(null);

        $component->redirectRoute('sales/order/view', ['order_id' => 5, '_query' => ['a' => 'b'], '_fragment' => 'top']);

        self::assertSame('https://shop.test/sales/order/view/?a=b#top', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        $params = ['order_id' => 5, '_query' => ['a' => 'b'], '_fragment' => 'top'];
        self::assertSame([['sales/order/view', $params]], $this->urlBuilderCalls);
    }

    public function test_redirect_route_can_return_a_relative_url_and_navigate(): void
    {
        $component = $this->component(null);

        $component->redirectRoute('checkout/cart', ['_query' => ['a' => 'b'], '_fragment' => 'top'], false, true);

        self::assertSame('/checkout/cart/?a=b#top', store($component)->get('redirect'));
        self::assertTrue(store($component)->get('redirectUsingNavigate'));
    }

    public function test_redirect_route_accepts_magento_null_parameters(): void
    {
        $component = $this->component(null);

        $component->redirectRoute('checkout/cart', null);

        self::assertSame('https://shop.test/checkout/cart/', store($component)->get('redirect'));
        self::assertSame([['checkout/cart', null]], $this->urlBuilderCalls);
    }

    public static function compatibilityModes(): array
    {
        return [
            'unmarked' => [null],
            'explicitly opted out' => [false],
            'enabled' => [true]
        ];
    }

    #[DataProvider('compatibilityModes')]
    public function test_array_parameters_resolve_a_route_regardless_of_backwards_compatibility(
        bool|null $bc
    ): void {
        $component = $this->component($bc);

        $component->redirect('checkout/cart', ['_query' => ['reason' => 'stock']], false);

        self::assertSame('https://shop.test/checkout/cart/?reason=stock', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([['checkout/cart', ['_query' => ['reason' => 'stock']]]], $this->urlBuilderCalls);
        self::assertSame($bc, store($component)->get('magewire:bc'));
    }

    #[DataProvider('compatibilityModes')]
    public function test_a_null_second_argument_keeps_the_native_behavior(
        bool|null $bc
    ): void {
        $component = $this->component($bc);

        $component->redirect('checkout/cart', null);

        self::assertSame('checkout/cart', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_the_native_signature_keeps_the_native_behavior_with_backwards_compatibility(): void
    {
        $component = $this->component(true);

        $component->redirect('/checkout/cart/', true);

        self::assertSame('/checkout/cart/', store($component)->get('redirect'));
        self::assertTrue(store($component)->get('redirectUsingNavigate'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_a_third_argument_alone_keeps_the_native_behavior_with_backwards_compatibility(): void
    {
        $component = $this->component(true);

        $component->redirect('/checkout/cart/', true, false);

        self::assertSame('/checkout/cart/', store($component)->get('redirect'));
        self::assertTrue(store($component)->get('redirectUsingNavigate'));
    }

    public function test_route_params_build_the_url_once(): void
    {
        $component = $this->component(true);

        $component->redirect('/checkout/cart', ['_query' => ['reason' => 'stock']]);

        self::assertSame('https://shop.test/checkout/cart/?reason=stock', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertTrue(store($component)->get('skipRender'));
        self::assertSame([['checkout/cart', ['_query' => ['reason' => 'stock']]]], $this->urlBuilderCalls);
    }

    #[DataProvider('compatibilityModes')]
    public function test_null_params_with_an_explicit_scheme_argument_build_the_route(bool|null $bc): void
    {
        $component = $this->component($bc);

        $component->redirect('checkout/cart', null, false);

        self::assertSame('https://shop.test/checkout/cart/', store($component)->get('redirect'));
        self::assertSame([['checkout/cart', []]], $this->urlBuilderCalls);
    }

    public function test_root_with_params_redirects_to_the_default_front_route(): void
    {
        $component = $this->component(true);

        $component->redirect('/', ['_query' => ['a' => 'b']]);

        self::assertSame([['cms', ['_query' => ['a' => 'b']]]], $this->urlBuilderCalls);
    }

    public static function wwwUrls(): array
    {
        return [
            'secure by default' => [[[]], 'https://example.com/landing'],
            'insecure' => [[[], false], 'http://example.com/landing'],
            'secure' => [[[], true], 'https://example.com/landing'],
            'null params, insecure' => [[null, false], 'http://example.com/landing'],
            'null params, secure' => [[null, true], 'https://example.com/landing']
        ];
    }

    #[DataProvider('wwwUrls')]
    public function test_www_urls_get_the_requested_scheme(array $arguments, string $expected): void
    {
        $component = $this->component(true);

        $component->redirect('www.example.com/landing', ...$arguments);

        self::assertSame($expected, store($component)->get('redirect'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public static function absoluteUrls(): array
    {
        return [
            'params become the query' => ['https://example.com/landing', 'https://example.com/landing?reason=stock'],
            'the query goes before the fragment' => [
                'https://example.com/landing#details',
                'https://example.com/landing?reason=stock#details'
            ],
            'a ? in the fragment is no query' => [
                'https://example.com/landing#details?tab=2',
                'https://example.com/landing?reason=stock#details?tab=2'
            ],
            'an existing query is kept' => [
                'https://example.com/landing?from=cart#details',
                'https://example.com/landing?from=cart#details'
            ],
            'an empty query is replaced' => ['https://example.com/landing?', 'https://example.com/landing?reason=stock'],
            'a protocol-relative URL' => ['//example.com/landing', '//example.com/landing?reason=stock'],
            'a zero fragment is preserved' => ['https://example.com/landing#0', 'https://example.com/landing?reason=stock#0'],
            'a zero query is preserved' => ['https://example.com/landing?0#details', 'https://example.com/landing?0#details']
        ];
    }

    #[DataProvider('absoluteUrls')]
    public function test_absolute_urls_take_the_params_as_query(string $url, string $expected): void
    {
        $component = $this->component(null);

        $component->redirect($url, ['reason' => 'stock']);

        self::assertSame($expected, store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_absolute_url_without_params_is_kept(): void
    {
        $component = $this->component(true);

        $component->redirect('https://example.com/landing#details', []);

        self::assertSame('https://example.com/landing#details', store($component)->get('redirect'));
    }

    public function test_an_empty_parameter_array_still_resolves_the_route_without_navigate(): void
    {
        $component = $this->component(null);

        $component->redirect('checkout/cart', []);

        self::assertSame('https://shop.test/checkout/cart/', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([['checkout/cart', []]], $this->urlBuilderCalls);
    }

    public function test_secure_route_parameters_are_forwarded_to_magento(): void
    {
        $component = $this->component(null);

        $component->redirect('checkout/cart', ['_secure' => false], true);

        self::assertSame([['checkout/cart', ['_secure' => false]]], $this->urlBuilderCalls);
    }

    public function test_the_scheme_argument_does_not_override_an_absolute_url(): void
    {
        $component = $this->component(null);

        $component->redirect('https://example.com/landing#details', ['reason' => 'stock'], false);

        self::assertSame('https://example.com/landing?reason=stock#details', store($component)->get('redirect'));
    }

    public function test_redirect_can_leave_rendering_enabled(): void
    {
        $component = $this->component(null);
        $this->configValues['livewire.render_on_redirect'] = true;

        $component->redirect('checkout/cart', ['_query' => ['reason' => 'stock']]);

        self::assertSame('https://shop.test/checkout/cart/?reason=stock', store($component)->get('redirect'));
        self::assertFalse(store($component)->has('skipRender'));
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
}
