<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use GuzzleHttp\Psr7\Uri;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Support\Url;
use Magewirephp\Magewire\Tests\Unit\Fixtures\CreatesUriFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fixtures/CreatesUriFactory.php';

class UrlTest extends TestCase
{
    use CreatesUriFactory;

    public static function relativeUrls(): array
    {
        return [
            'credentials and port' => [
                'https://user:password@shop.test:8443/store/cart/?a=1#details',
                '/store/cart/?a=1#details'
            ],
            'protocol relative' => ['//shop.test/store/cart/?a=1#details', '/store/cart/?a=1#details'],
            'root without path' => ['https://shop.test', '/'],
            'root with query and fragment' => ['https://shop.test?a=1#details', '/?a=1#details'],
            'zero query and fragment' => ['https://shop.test/cart?0#0', '/cart?0#0'],
            'already relative' => ['/store/cart/?a=1#details', '/store/cart/?a=1#details'],
            'encoded parts' => ['https://shop.test/store/%2Fcart?q=a%20b#details', '/store/%2Fcart?q=a%20b#details']
        ];
    }

    #[DataProvider('relativeUrls')]
    public function test_relative_urls_preserve_the_path_query_and_fragment(string $url, string $expected): void
    {
        $support = $this->support($url);

        self::assertSame($expected, $support->relative($url));
    }

    public function test_absolute_query_parameters_are_encoded_before_the_fragment(): void
    {
        $support = $this->support('https://example.com/landing#details');

        self::assertSame('https://example.com/landing?reason=out+of+stock&return=%2Fcart%3Fx%3D1#details', $support->resolve('https://example.com/landing#details', [
            'reason' => 'out of stock',
            'return' => '/cart?x=1'
        ]));
    }

    public function test_each_url_is_created_through_the_object_manager(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager
            ->expects(self::exactly(2))
            ->method('create')
            ->with(self::callback(static fn ($class) => ltrim($class, '\\') === Uri::class), self::anything())
            ->willReturnCallback(static fn ($class, $arguments) => new Uri(str_replace('example.com', 'preferred.test', $arguments['uri'])));
        $support = new Url($this->createStub(UrlInterface::class), $this->createStub(ScopeConfigInterface::class), $this->uriFactory($objectManager));

        self::assertSame('https://preferred.test/first?reason=stock#details', $support->resolve('https://example.com/first#details', ['reason' => 'stock']));
        self::assertSame('/second?0#0', $support->relative('https://example.com/second?0#0'));
    }

    private function support(string $url): Url
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn ($class) => ltrim($class, '\\') === Uri::class), ['uri' => $url])
            ->willReturn(new Uri($url));

        return new Url($this->createStub(UrlInterface::class), $this->createStub(ScopeConfigInterface::class), $this->uriFactory($objectManager));
    }
}
