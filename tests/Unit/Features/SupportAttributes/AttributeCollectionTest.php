<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportAttributes;

use Magewirephp\Magewire\Attributes\Lazy;
use Magewirephp\Magewire\Attributes\On;
use Magewirephp\Magewire\Features\SupportAttributes\AttributeCollection;
use Magewirephp\Magewire\Features\SupportAttributes\AttributeLevel;
use Magewirephp\Magewire\Tests\Unit\Fixtures\AttributedComponent;
use Magewirephp\Magewire\Tests\Unit\Fixtures\AttributedComponentInterceptor;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Fixtures/AttributedComponent.php';
require_once __DIR__ . '/../../Fixtures/AttributedComponentInterceptor.php';

class AttributeCollectionTest extends TestCase
{
    public function test_it_collects_the_attributes_of_a_component(): void
    {
        self::assertSame(['onKept', 'onPlugged'], $this->listenerMethods(new AttributedComponent()));
    }

    public function test_it_collects_the_attributes_of_plugged_methods_through_a_magento_interceptor(): void
    {
        $component = new AttributedComponentInterceptor();
        $attributes = AttributeCollection::fromComponent($component);

        self::assertSame(['onKept', 'onPlugged'], $this->listenerMethods($component));
        self::assertCount(1, $attributes->filter(static fn ($attribute) => $attribute instanceof Lazy && $attribute->getLevel() === AttributeLevel::ROOT));
        self::assertSame($component, $attributes->first()->getComponent());
    }

    private function listenerMethods(AttributedComponent $component): array
    {
        $methods = AttributeCollection::fromComponent($component)
            ->filter(static fn ($attribute) => $attribute instanceof On && $attribute->getLevel() === AttributeLevel::METHOD)
            ->map(static fn (On $attribute) => $attribute->getName())
            ->values()
            ->all();

        sort($methods);

        return $methods;
    }
}
