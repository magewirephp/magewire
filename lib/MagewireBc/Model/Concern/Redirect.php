<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magewirephp\Magewire\Support\Factory;

/**
 * @deprecated Magewire 1's redirect($path, $params = null, $secure = true). Use redirect($url) or redirectRoute().
 */
trait Redirect
{
    /**
     * Redirects as Magewire 1 did, for components with backwards compatibility enabled; redirect() hands over the
     * calls with Magewire 1's signature.
     *
     * - A path is a Magento route, built with the params by the URL builder (redirectRoute()). As in v1, '/' with
     *   params is the default front route.
     * - An absolute URL is kept; the params become its query, unless it has one already.
     * - 'www.' URLs get 'https://', or 'http://' when $secure is false. Like in v1, $secure only applies to them.
     *
     * @deprecated Use redirect($url) or redirectRoute() instead.
     * @mago-expect lint:no-boolean-flag-parameter
     */
    protected function magewireRedirectV1(string $path, array|null $params = null, bool $secure = true): void
    {
        $params ??= [];

        if (str_starts_with($path, 'www.')) {
            $path = ( $secure ? 'https://' : 'http://' ) . substr($path, 4);
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*:)?//#i', $path) === 1) {
            $this->redirect(self::magewireAddQueryToUrl($path, $params), false);

            return;
        }

        if ($path === '/' && $params !== []) {
            // 'store' is Magento\Store\Model\ScopeInterface::SCOPE_STORE; Magewire only requires the framework.
            $path = (string) Factory::get(ScopeConfigInterface::class)->getValue('web/default/front', 'store');
        }

        $this->redirectRoute($path === '/' ? $path : ltrim($path, '/'), $params);
    }

    /**
     * Adds the params as the query of the URL, before its fragment, unless the URL has a query already.
     */
    private static function magewireAddQueryToUrl(string $url, array $params): string
    {
        [$base, $fragment] = array_pad(explode('#', $url, 2), 2, null);

        if ($params === [] || ( parse_url($base, PHP_URL_QUERY) ?? '' ) !== '') {
            return $url;
        }

        return rtrim($base, '?') . '?' . http_build_query($params) . ( $fragment === null ? '' : '#' . $fragment );
    }
}
