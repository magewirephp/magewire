<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportStreaming;

use Magewirephp\Magewire\Features\SupportStreaming\SupportStreaming;
use Magewirephp\Magewire\ComponentHookRegistry;
trait HandlesStreaming
{
    function stream($to, $content, $replace = false)
    {
        $hook = ComponentHookRegistry::getHook($this, SupportStreaming::class);
        $hook->stream($to, $content, $replace);
    }
}