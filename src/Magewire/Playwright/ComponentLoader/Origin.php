<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\ComponentLoader;

use Magewirephp\Magewire\Component;

class Origin extends Component
{
    public int $count = 0;

    public function run(): void
    {
        $this->count++;
        $this->dispatch('component-loader:follow-up');
    }
}
