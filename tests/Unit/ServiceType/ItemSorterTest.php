<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\ServiceType;

use LogicException;
use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;
use Magewirephp\Magewire\ServiceType\ItemSorter;
use PHPUnit\Framework\TestCase;

/** @mago-expect lint:too-many-methods */
class ItemSorterTest extends TestCase
{
    private ItemSorter $sorter;

    protected function setUp(): void
    {
        $this->sorter = new ItemSorter();
    }

    public function test_it_preserves_sort_order_and_insertion_order_without_sequences(): void
    {
        $items = [
            'late' => $this->item(300),
            'equal_first' => $this->item(200),
            'early' => $this->item(100),
            'equal_second' => $this->item(200)
        ];

        self::assertSame(['early', 'equal_first', 'equal_second', 'late'], array_keys($this->sorter->sort($items)));
    }

    public function test_it_loads_an_item_after_its_sequence_even_when_sort_order_conflicts(): void
    {
        $items = [
            'dependent' => $this->item(100, ['dependency' => true]),
            'dependency' => $this->item(200)
        ];

        self::assertSame(['dependency', 'dependent'], array_keys($this->sorter->sort($items)));
    }

    public function test_it_resolves_transitive_sequences(): void
    {
        $items = [
            'third' => $this->item(100, ['second' => true]),
            'second' => $this->item(200, ['first' => true]),
            'first' => $this->item(300)
        ];

        self::assertSame(['first', 'second', 'third'], array_keys($this->sorter->sort($items)));
    }

    public function test_it_waits_for_every_present_sequence(): void
    {
        $items = [
            'dependent' => $this->item(100, ['first' => true, 'second' => true]),
            'second' => $this->item(200),
            'first' => $this->item(300)
        ];

        self::assertSame(['second', 'first', 'dependent'], array_keys($this->sorter->sort($items)));
    }

    public function test_it_ignores_disabled_and_missing_sequences(): void
    {
        $items = [
            'dependent' => $this->item(100, ['present' => false, 'missing' => true]),
            'present' => $this->item(200)
        ];

        self::assertSame(['dependent', 'present'], array_keys($this->sorter->sort($items)));
    }

    public function test_it_rejects_circular_sequences(): void
    {
        $items = [
            'first' => $this->item(100, ['second' => true]),
            'second' => $this->item(200, ['third' => true]),
            'third' => $this->item(300, ['first' => true])
        ];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Circular service type sequence detected among items: "first", "second", "third".');

        $this->sorter->sort($items);
    }

    public function test_it_rejects_an_eager_item_sequenced_after_a_lazy_item(): void
    {
        $items = [
            'eager' => $this->item(100, ['lazy' => true], ServiceTypeItemBootMode::PERSISTENT),
            'lazy' => $this->item(200, bootMode: ServiceTypeItemBootMode::LAZY)
        ];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service type item "eager" cannot sequence after lazy item "lazy".');

        $this->sorter->sort($items);
    }

    public function test_it_allows_setup_items_to_sequence_each_other(): void
    {
        $items = [
            'always' => $this->item(100, ['persistent' => true], ServiceTypeItemBootMode::ALWAYS),
            'persistent' => $this->item(200, bootMode: ServiceTypeItemBootMode::PERSISTENT)
        ];

        self::assertSame(['persistent', 'always'], array_keys($this->sorter->sort($items)));
    }

    public function test_it_allows_a_lazy_item_to_sequence_after_an_eager_item(): void
    {
        $items = [
            'lazy' => $this->item(100, ['eager' => true], ServiceTypeItemBootMode::LAZY),
            'eager' => $this->item(200, bootMode: ServiceTypeItemBootMode::ALWAYS)
        ];

        self::assertSame(['eager', 'lazy'], array_keys($this->sorter->sort($items)));
    }

    /**
     * @param array<string, bool> $sequence
     * @return array<string, mixed>
     */
    private function item(
        int $sortOrder,
        array $sequence = [],
        ServiceTypeItemBootMode $bootMode = ServiceTypeItemBootMode::LAZY
    ): array {
        return [
            'sort_order' => $sortOrder,
            'sequence' => $sequence,
            'boot_mode' => $bootMode
        ];
    }
}
