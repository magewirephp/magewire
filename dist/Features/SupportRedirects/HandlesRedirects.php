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

use function Magewirephp\Magewire\config;
use function Magewirephp\Magewire\store;
trait HandlesRedirects
{
    public function redirect($url, $navigate = false)
    {
        store($this)->set('redirect', $url);
        if ($navigate) {
            store($this)->set('redirectUsingNavigate', true);
        }
        $shouldSkipRender = !config('livewire.render_on_redirect', false);
        $shouldSkipRender && $this->skipRender();
    }
}