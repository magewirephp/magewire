<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportRedirects;

use Magewirephp\Magewire\Containers\Redirect;
use function Magewirephp\Magewire\app;
use function Magewirephp\Magewire\config;
use Magewirephp\Magewire\Mechanisms\HandleRequests\HandleRequests;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Component;
use function Magewirephp\Magewire\on;
class SupportRedirects extends ComponentHook
{
    public static $redirectorCacheStack = [];
    public static $atLeastOneMountedComponentHasRedirected = false;
    public static function provide()
    {
        // Wait until all components have been processed...
        on('response', function ($response) {
            // If there was no redirect on a subsequent component update, clear flash session data.
            if (!static::$atLeastOneMountedComponentHasRedirected && app()->has('session.store')) {
                session()->forget(session()->get('_flash.new'));
            }
        });
        on('flush-state', function () {
            static::$atLeastOneMountedComponentHasRedirected = false;
        });
    }
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
        if (!$context->isMounting()) {
            static::$atLeastOneMountedComponentHasRedirected = true;
        }
    }
}