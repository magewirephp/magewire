<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Model\View\Utils;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewire;
use Magewirephp\Magewire\Model\View\Utils\Magewire;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Builder;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Features;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Mechanisms;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionProperty;

class MagewireTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousObjectManager = (new ReflectionProperty(ObjectManager::class, '_instance'))->getValue();
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, $this->previousObjectManager);

        parent::tearDown();
    }

    public static function baseUrls(): array
    {
        return [
            'root' => ['https://example.com/', '/magewire/update'],
            'store code' => ['https://example.com/lt/', '/lt/magewire/update'],
            'subdirectory and store code' => ['https://example.com/shop/lt/', '/shop/lt/magewire/update'],
            'no trailing slash' => ['https://example.com', '/magewire/update']
        ];
    }

    #[DataProvider('baseUrls')]
    public function test_update_uri_is_relative_to_the_store_base_url(string $baseUrl, string $expected): void
    {
        self::assertSame($expected, $this->createMagewire($this->createUrl($baseUrl))->getUpdateUri());
    }

    public function test_the_previous_constructor_signature_resolves_the_url_from_the_object_manager(): void
    {
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnMap([[UrlInterface::class, $this->createUrl('https://example.com/lt/')]]);
        ObjectManager::setInstance($objectManager);

        self::assertSame('/lt/magewire/update', $this->createMagewire()->getUpdateUri());
    }

    private function createUrl(string $baseUrl): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getBaseUrl')->willReturn($baseUrl);

        return $url;
    }

    private function createMagewire(UrlInterface|null ...$url): Magewire
    {
        $dependency = static fn (string $type): object => (new ReflectionClass($type))->newInstanceWithoutConstructor();

        return new Magewire(
            $dependency(Builder::class),
            $dependency(Features::class),
            $dependency(Mechanisms::class),
            $dependency(ConfigMagewire::class),
            $this->createStub(LoggerInterface::class),
            ...$url
        );
    }
}
