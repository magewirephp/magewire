<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\LoadingIndicator;

use Magewirephp\Magewire\Component;

class Listener extends Component
{
    public int $count = 0;

    protected $listeners = ['loading-indicator:follow-up' => 'run'];

    public function run(): void
    {
        $this->count++;
    }
}
