<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\BackwardsCompatibility\EmitUp;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;

/**
 * A node of the emitUp() fixture tree: it counts the "node:picked" events it receives and has no "$refresh"
 * listener, so a re-render can only come from emitToRefreshUp().
 */
#[HandleBackwardsCompatibility]
class Node extends Component
{
    public int $received = 0;

    protected $listeners = [
        'node:picked' => 'onPicked'
    ];

    public function pick(): void
    {
        $this->emitUp('node:picked');
    }

    public function pickSelf(): void
    {
        $this->emitUp('node:picked')->self();
    }

    public function pickStranger(): void
    {
        $this->emitUp('node:picked')->to('magewire.playwright.emit-up.stranger');
    }

    public function refreshUp(): void
    {
        $this->emitToRefreshUp();
    }

    public function broadcast(): void
    {
        $this->dispatch('node:picked');
    }

    public function onPicked(): void
    {
        $this->received++;
    }
}
