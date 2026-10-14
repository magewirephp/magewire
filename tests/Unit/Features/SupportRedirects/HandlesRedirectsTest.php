<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportRedirects;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Config;
use Magewirephp\Magewire\Mechanisms\DataStore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Magewirephp\Magewire\store;

/** @mago-expect lint:too-many-methods */
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
        $objectManager
            ->method('get')
            ->willReturnCallback(static fn ($class) => match ($class) {
                DataStore::class => $dataStore,
                Config::class => $config,
                UrlInterface::class => $urlBuilder,
                ScopeConfigInterface::class => $scopeConfig,
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

    public static function componentsWithoutBackwardsCompatibility(): array
    {
        return [
            'unmarked' => [null],
            'explicitly opted out' => [false]
        ];
    }

    #[DataProvider('componentsWithoutBackwardsCompatibility')]
    public function test_the_v1_signature_keeps_the_native_behavior_without_backwards_compatibility(
        bool|null $bc
    ): void {
        $component = $this->component($bc);

        $component->redirect('checkout/cart', ['_query' => ['reason' => 'stock']], false);

        // Natively, the second argument is $navigate: a non-empty array counts as true.
        self::assertSame('checkout/cart', store($component)->get('redirect'));
        self::assertTrue(store($component)->get('redirectUsingNavigate'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    #[DataProvider('componentsWithoutBackwardsCompatibility')]
    public function test_a_null_second_argument_keeps_the_native_behavior_without_backwards_compatibility(
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

    public function test_v1_route_params_build_the_url_through_redirect_route_once(): void
    {
        $component = $this->component(true);

        $component->redirect('/checkout/cart', ['_query' => ['reason' => 'stock']]);

        // redirectRoute() calls redirect($url, false), which takes the native path: one URL, built once.
        self::assertSame('https://shop.test/checkout/cart/?reason=stock', store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertTrue(store($component)->get('skipRender'));
        self::assertSame([['checkout/cart', ['_query' => ['reason' => 'stock']]]], $this->urlBuilderCalls);
    }

    public function test_v1_null_params_build_the_url_of_the_path(): void
    {
        $component = $this->component(true);

        $component->redirect('checkout/cart', null);

        self::assertSame('https://shop.test/checkout/cart/', store($component)->get('redirect'));
        self::assertSame([['checkout/cart', []]], $this->urlBuilderCalls);
    }

    public function test_v1_root_with_params_redirects_to_the_default_front_route(): void
    {
        $component = $this->component(true);

        $component->redirect('/', ['_query' => ['a' => 'b']]);

        self::assertSame([['cms', ['_query' => ['a' => 'b']]]], $this->urlBuilderCalls);
    }

    public static function v1WwwUrls(): array
    {
        return [
            'secure by default' => [[], 'https://example.com/landing'],
            'insecure' => [[[], false], 'http://example.com/landing'],
            'secure' => [[[], true], 'https://example.com/landing']
        ];
    }

    #[DataProvider('v1WwwUrls')]
    public function test_v1_www_urls_get_the_scheme_secure_asks_for(array $arguments, string $expected): void
    {
        $component = $this->component(true);

        $arguments = $arguments === [] ? [null] : $arguments;
        $component->redirect('www.example.com/landing', ...$arguments);

        self::assertSame($expected, store($component)->get('redirect'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public static function v1AbsoluteUrls(): array
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
            'a protocol-relative URL' => ['//example.com/landing', '//example.com/landing?reason=stock']
        ];
    }

    #[DataProvider('v1AbsoluteUrls')]
    public function test_v1_absolute_urls_take_the_params_as_query(string $url, string $expected): void
    {
        $component = $this->component(true);

        $component->redirect($url, ['reason' => 'stock']);

        self::assertSame($expected, store($component)->get('redirect'));
        self::assertNull(store($component)->get('redirectUsingNavigate'));
        self::assertSame([], $this->urlBuilderCalls);
    }

    public function test_v1_absolute_url_without_params_is_kept(): void
    {
        $component = $this->component(true);

        $component->redirect('https://example.com/landing#details', []);

        self::assertSame('https://example.com/landing#details', store($component)->get('redirect'));
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
