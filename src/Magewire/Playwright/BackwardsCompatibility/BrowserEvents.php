<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\BackwardsCompatibility;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;

#[HandleBackwardsCompatibility]
class BrowserEvents extends Component
{
    public function legacy(string $payload): void
    {
        $data = match ($payload) {
            'empty' => null,
            'array' => ['id' => 5],
            'object' => (object) ['id' => 5],
            'string' => 'saved'
        };

        $this->dispatchBrowserEvent('playwright:legacy', $data);
    }

    public function modern(string $payload): void
    {
        $params = match ($payload) {
            'empty' => [],
            'named' => ['id' => 5],
            'string' => ['saved']
        };

        $this->dispatch('playwright:modern', ...$params);
    }
}
