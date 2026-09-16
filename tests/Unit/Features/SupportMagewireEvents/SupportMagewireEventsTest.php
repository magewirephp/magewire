<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireEvents;

use Magewirephp\Magewire\Features\SupportMagewireEvents\SupportMagewireEvents;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SupportMagewireEventsTest extends TestCase
{
    public function test_it_layers_normalized_listener_sources_in_order(): void
    {
        $listeners = $this->mergeListenerSources(
            [
                'shorthandListener',
                'class:kept' => 'onClassKept',
                'shared:event' => 'onClassShared'
            ],
            [
                'attribute:kept' => 'onAttributeKept',
                'shared:event' => 'onAttributeShared'
            ],
            [
                'layout:added' => 'onLayoutAdded',
                'shared:event' => 'onLayoutShared'
            ]
        );

        self::assertSame(
            [
                'shorthandListener' => 'shorthandListener',
                'class:kept' => 'onClassKept',
                'shared:event' => 'onLayoutShared',
                'attribute:kept' => 'onAttributeKept',
                'layout:added' => 'onLayoutAdded'
            ],
            $listeners
        );
    }

    public function test_false_and_null_remove_listeners_from_earlier_sources(): void
    {
        $listeners = $this->mergeListenerSources([
            'class:removed' => 'onClassRemoved',
            'class:null-removed' => 'onClassNullRemoved',
            'class:kept' => 'onClassKept'
        ], [
            'class:removed' => false,
            'class:null-removed' => null
        ]);

        self::assertSame(['class:kept' => 'onClassKept'], $listeners);
    }

    private function mergeListenerSources(array ...$sources): array
    {
        $method = new ReflectionMethod(SupportMagewireEvents::class, 'mergeListenerSources');

        return $method->invoke(null, ...$sources);
    }
}
