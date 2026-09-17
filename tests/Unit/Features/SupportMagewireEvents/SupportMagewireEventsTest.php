<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireEvents;

use Magewirephp\Magewire\Features\SupportMagewireEvents\SupportMagewireEvents;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SupportMagewireEventsTest extends TestCase
{
    public function test_it_layers_layout_handlers_over_attribute_listeners(): void
    {
        $listeners = $this->applyListenerOverlay([
            'shorthandListener',
            'attribute:removed' => 'onAttributeRemoved',
            'shared:event' => 'onAttributeShared'
        ], [
            'attribute:removed' => false,
            'layout:added' => 'onLayoutAdded',
            'shared:event' => 'onLayoutShared'
        ]);

        self::assertSame(
            [
                'shorthandListener' => 'shorthandListener',
                'shared:event' => 'onLayoutShared',
                'layout:added' => 'onLayoutAdded'
            ],
            $listeners
        );
    }

    public function test_false_and_null_are_preserved_as_listener_tombstones(): void
    {
        $tombstones = $this->getListenerTombstones([
            'class:removed' => false,
            'class:null-removed' => null,
            'layout:kept' => 'onLayoutKept'
        ]);

        self::assertSame(
            [
                'class:removed' => false,
                'class:null-removed' => null
            ],
            $tombstones
        );
    }

    private function applyListenerOverlay(array $listeners, array $overlay): array
    {
        $method = new ReflectionMethod(SupportMagewireEvents::class, 'applyListenerOverlay');

        return $method->invoke(null, $listeners, $overlay);
    }

    private function getListenerTombstones(array $listeners): array
    {
        $method = new ReflectionMethod(SupportMagewireEvents::class, 'getListenerTombstones');

        return $method->invoke(null, $listeners);
    }
}
