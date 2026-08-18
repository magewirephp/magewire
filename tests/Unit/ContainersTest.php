<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

use Magewirephp\Magewire\Containers;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Fixtures/ApplicationContainerFixture.php';

class ContainersTest extends TestCase
{
    public function test_it_inspects_aliases_without_resolving_them(): void
    {
        $containers = new Containers(['livewire' => ApplicationContainerFixture::class]);

        self::assertTrue($containers->has('livewire'));
        self::assertSame(ApplicationContainerFixture::class, $containers->itemType('livewire'));
        self::assertFalse($containers->has('missing'));
    }

    public function test_it_retains_the_requested_type_after_assembly(): void
    {
        $service = new ApplicationContainerFixture('shared');
        $containers = new Containers(['livewire' => ['type' => $service]]);

        self::assertSame($service, $containers->item('livewire'));
        self::assertSame(ApplicationContainerFixture::class, $containers->itemType('livewire'));
    }
}
