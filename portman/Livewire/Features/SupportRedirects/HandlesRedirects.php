<?php

namespace Magewirephp\Magewire\Features\SupportRedirects;

use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Support\Url;

use function Magewirephp\Magewire\config;
use function Magewirephp\Magewire\store;

trait HandlesRedirects
{
    /**
     * Passing an array of URL parameters, or null with a third scheme argument, is deprecated.
     * Use redirectRoute() for routes or redirect() with a complete URL.
     */
    public function redirect($url, $navigate = false)
    {
        if (is_array($navigate) || $navigate === null && func_num_args() > 2) {
            $url = Factory::get(Url::class)->resolve($url, $navigate ?? [], func_num_args() > 2 ? func_get_arg(2) : true);
            $navigate = false;
        }

        store($this)->set('redirect', $url);

        if ($navigate) {
            store($this)->set('redirectUsingNavigate', true);
        }

        $shouldSkipRender = ! config('livewire.render_on_redirect', false);

        $shouldSkipRender && $this->skipRender();
    }

    /**
     * Redirects to a Magento route, as Livewire's redirectRoute() does to a Laravel route.
     *
     * The parameters are Magento route params, e.g. ['id' => 5, '_query' => ['a' => 'b'], '_fragment' => 'top'].
     */
    public function redirectRoute($name, $parameters = [], $absolute = true, $navigate = false)
    {
        $this->redirect(Factory::get(Url::class)->route($name, $parameters, $absolute), $navigate);
    }
}
