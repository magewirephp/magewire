<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireEvents;

use Magewirephp\Magewire\Features\SupportMagewireEvents\SupportMagewireEvents;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutArgumentOverlay;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SupportMagewireEventsTest extends TestCase
{
    public function test_it_layers_layout_handlers_over_attribute_listeners(): void
    {
        $listeners = LayoutArgumentOverlay::apply(
            $this->normalizeListeners([
                'shorthandListener',
                'attribute:removed' => 'onAttributeRemoved',
                'shared:event' => 'onAttributeShared'
            ]),
            $this->normalizeListeners([
                'attribute:removed' => false,
                'layout:added' => 'onLayoutAdded',
                'shared:event' => 'onLayoutShared'
            ]),
            [false, null]
        );

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
        $tombstones = LayoutArgumentOverlay::removed($this->normalizeListeners([
            'class:removed' => false,
            'class:null-removed' => null,
            'layout:kept' => 'onLayoutKept'
        ]), [false, null]);

        self::assertSame(
            [
                'class:removed' => false,
                'class:null-removed' => null
            ],
            $tombstones
        );
    }

    private function normalizeListeners(array $listeners): array
    {
        $method = new ReflectionMethod(SupportMagewireEvents::class, 'normalizeListeners');

        return $method->invoke(null, $listeners);
    }
}
