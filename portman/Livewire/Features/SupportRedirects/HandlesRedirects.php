<?php

namespace Magewirephp\Magewire\Features\SupportRedirects;

use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Support\Factory;

use function Magewirephp\Magewire\config;
use function Magewirephp\Magewire\store;

trait HandlesRedirects
{
    /**
     * Redirects the browser to the given URL, optionally using wire:navigate.
     *
     * Magewire v1's signature redirect($path, $params = null, $secure = true) is supported as well:
     * an array (or null) as second argument holds Magento route params, and the URL is built from
     * the path with Magento's URL builder, e.g. redirect('checkout/cart', ['_query' => ['a' => 'b']]).
     */
    public function redirect($url, $navigate = false)
    {
        if (is_array($navigate) || $navigate === null) {
            $url = $this->magewireRedirectUrl((string) $url, $navigate ?? [], func_num_args() > 2 ? (bool) func_get_arg(2) : null);
            $navigate = false;
        }

        store($this)->set('redirect', $url);

        if ($navigate) store($this)->set('redirectUsingNavigate', true);

        $shouldSkipRender = ! config('livewire.render_on_redirect', false);

        $shouldSkipRender && $this->skipRender();
    }

    private function magewireRedirectUrl(string $url, array $params, bool|null $secure): string
    {
        // An absolute URL is not a route: the params only become its query, when it has none (as in v1).
        if (preg_match('#^([a-z][a-z0-9+.-]*:)?//#i', $url)) {
            return $params === [] || str_contains($url, '?') ? $url : $url . '?' . http_build_query($params);
        }
        if ($secure !== null) {
            $params += ['_secure' => $secure];
        }

        return Factory::get(UrlInterface::class)->getUrl(ltrim($url, '/'), $params);
    }
}
