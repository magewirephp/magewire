<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;
use Magewirephp\Magewire\Support\AttributesReader;
use PHPUnit\Framework\TestCase;

#[HandleBackwardsCompatibility(enabled: false)]
class AttributesReaderParentFixture
{
}

class AttributesReaderChildFixture extends AttributesReaderParentFixture
{
}

#[HandleBackwardsCompatibility(enabled: true)]
class AttributesReaderOverridingChildFixture extends AttributesReaderParentFixture
{
}

class AttributesReaderTest extends TestCase
{
    public function test_first_in_hierarchy_reads_a_parent_class_attribute(): void
    {
        $attribute = AttributesReader::for(new AttributesReaderChildFixture())->firstInHierarchy(HandleBackwardsCompatibility::class);

        self::assertInstanceOf(HandleBackwardsCompatibility::class, $attribute);
        self::assertFalse($attribute->isBackwardsCompatible());
    }

    public function test_first_in_hierarchy_prefers_the_nearest_class(): void
    {
        $attribute = AttributesReader::for(AttributesReaderOverridingChildFixture::class)->firstInHierarchy(HandleBackwardsCompatibility::class);

        self::assertTrue($attribute?->isBackwardsCompatible());
    }

    public function test_first_in_hierarchy_returns_null_without_the_attribute(): void
    {
        self::assertNull(AttributesReader::for(new \stdClass())->firstInHierarchy(HandleBackwardsCompatibility::class));
    }
}
