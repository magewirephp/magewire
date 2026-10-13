<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use Magewirephp\Magewire\Attributes\Lazy;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;
use Magewirephp\Magewire\Support\AttributesReader;
use Magewirephp\Magewire\Tests\Unit\Fixtures\AttributedComponent;
use Magewirephp\Magewire\Tests\Unit\Fixtures\AttributedComponentInterceptor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../Fixtures/AttributedComponent.php';
require_once __DIR__ . '/../Fixtures/AttributedComponentInterceptor.php';

class AttributesReaderInterceptorTest extends TestCase
{
    public function test_it_reads_class_attributes_of_a_component(): void
    {
        $attribute = AttributesReader::for(new AttributedComponent())->first(HandleBackwardsCompatibility::class);

        self::assertInstanceOf(HandleBackwardsCompatibility::class, $attribute);
        self::assertFalse($attribute->isBackwardsCompatible());
    }

    public function test_it_reads_class_attributes_through_a_magento_interceptor(): void
    {
        foreach ([new AttributedComponentInterceptor(), AttributedComponentInterceptor::class, new ReflectionClass(AttributedComponentInterceptor::class)] as $subject) {
            $attribute = AttributesReader::for($subject)->first(HandleBackwardsCompatibility::class);

            self::assertInstanceOf(HandleBackwardsCompatibility::class, $attribute);
            self::assertFalse($attribute->isBackwardsCompatible());
        }
    }

    public function test_the_lazy_attribute_is_found_through_a_magento_interceptor(): void
    {
        self::assertSame(AttributedComponent::class, Utils::getReflectionWithoutInterceptors(new AttributedComponentInterceptor())->getName());
        self::assertNotEmpty(Utils::getReflectionWithoutInterceptors(new AttributedComponentInterceptor())->getAttributes(Lazy::class));
        self::assertSame(AttributedComponent::class, Utils::getReflectionWithoutInterceptors(new AttributedComponent())->getName());
    }
}
