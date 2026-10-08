<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\MagewireBc;

use ArrayIterator;
use Magewirephp\Magewire\Model\Concern\BrowserEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BrowserEventTest extends TestCase
{
    public static function payloads(): array
    {
        return [
            'no data' => [null, []],
            'named data' => [['id' => 5], ['id' => 5]],
            'iterable data' => [new ArrayIterator(['id' => 5]), ['id' => 5]],
            'scalar data' => ['saved', ['saved']]
        ];
    }

    #[DataProvider('payloads')]
    public function test_dispatch_browser_event_forwards_any_v1_payload(mixed $data, array $expected): void
    {
        $component = new class {
            use BrowserEvent;

            public array $dispatched = [];

            public function dispatch($event, ...$params)
            {
                $this->dispatched = [$event, $params];
            }
        };

        $component->dispatchBrowserEvent('item-saved', $data);

        self::assertSame(['item-saved', $expected], $component->dispatched);
    }
}
