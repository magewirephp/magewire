<?php

namespace Magewirephp\Magewire\Features\SupportStreaming;

use Livewire\Features\SupportStreaming\SupportStreaming;
use Magewirephp\Magewire\ComponentHookRegistry;

trait HandlesStreaming
{
    function stream($to, $content, $replace = false)
    {
        $hook = ComponentHookRegistry::getHook($this, SupportStreaming::class);
        $hook->stream($to, $content, $replace);
    }
}
