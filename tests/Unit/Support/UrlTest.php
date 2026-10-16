<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Support\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
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
        $support = new Url($this->createStub(UrlInterface::class), $this->createStub(ScopeConfigInterface::class));

        self::assertSame($expected, $support->relative($url));
    }

    public function test_absolute_query_parameters_are_encoded_before_the_fragment(): void
    {
        $support = new Url($this->createStub(UrlInterface::class), $this->createStub(ScopeConfigInterface::class));

        self::assertSame('https://example.com/landing?reason=out+of+stock&return=%2Fcart%3Fx%3D1#details', $support->resolve('https://example.com/landing#details', [
            'reason' => 'out of stock',
            'return' => '/cart?x=1'
        ]));
    }
}
