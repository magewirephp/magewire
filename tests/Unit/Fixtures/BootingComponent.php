<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use Magewirephp\Magewire\Component;

class BootingComponent extends Component
{
    public static array $calls = [];

    public int|null $foo = null;

    public function boot(): void
    {
        self::$calls[] = 'boot';

        if ($this->foo === null) {
            $this->foo = 1337;
        }
    }

    public function booted(): void
    {
        self::$calls[] = 'booted';
    }
}
