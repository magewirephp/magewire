<?php

namespace Magewirephp\Magewire\Features\SupportRedirects;

use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Support\Factory;

use function Magewirephp\Magewire\config;
use function Magewirephp\Magewire\store;

trait HandlesRedirects
{
    public function redirect($url, $navigate = false)
    {
        // Magewire 1's redirect($path, $params = null, $secure = true), for components with backwards compatibility.
        if (( is_array($navigate) || $navigate === null ) && store($this)->get('magewire:bc') === true) {
            $this->magewireRedirectV1($url, $navigate, ...array_slice(func_get_args(), 2, 1));

            return;
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
        $url = Factory::get(UrlInterface::class)->getUrl($name, $parameters);

        $this->redirect($absolute ? $url : self::magewireRelativeUrl($url), $navigate);
    }

    /**
     * Returns the URL without its scheme and host, as Laravel's route($name, $parameters, false) does.
     */
    private static function magewireRelativeUrl(string $url): string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        return ( parse_url($url, PHP_URL_PATH) ?? '/' ) . ( $query === null ? '' : '?' . $query ) . ( $fragment === null ? '' : '#' . $fragment );
    }
}
