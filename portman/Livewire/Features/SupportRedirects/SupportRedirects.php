<?php

namespace Magewirephp\Magewire\Features\SupportRedirects;

use Magewirephp\Magewire\Containers\Redirect;
use function Magewirephp\Magewire\app;
use function Magewirephp\Magewire\config;

class SupportRedirects extends \Livewire\Features\SupportRedirects\SupportRedirects
{
    public function boot()
    {
        /** @var Redirect $redirect */
        $redirect = app('redirect');

        $redirect->component($this->component);
    }

    public function dehydrate($context)
    {
        $to = $this->storeGet('redirect');
        $usingNavigate = $this->storeGet('redirectUsingNavigate');

        if ($to) {
            $context->addEffect('redirect', $to);
        }

        $usingNavigate && $context->addEffect('redirectUsingNavigate', true);

        if (! $context->isMounting()) {
            static::$atLeastOneMountedComponentHasRedirected = true;
        }
    }
}
