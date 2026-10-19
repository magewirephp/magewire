<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use GuzzleHttp\Psr7\UriFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

class Url
{
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly UriFactory $uriFactory
    ) {
    }

    /**
     * @mago-expect lint:no-boolean-flag-parameter
     */
    public function route(string|null $name, array|null $parameters = [], bool $absolute = true): string
    {
        $url = $this->urlBuilder->getUrl($name, $parameters);

        return $absolute ? $url : $this->relative($url);
    }

    /**
     * Resolves a Magento route or an absolute URL with parameters.
     * The secure argument chooses the scheme only for a path starting with "www.".
     *
     * @mago-expect lint:no-boolean-flag-parameter
     */
    public function resolve(string $path, array $parameters = [], bool $secure = true): string
    {
        if (str_starts_with($path, 'www.')) {
            $path = ( $secure ? 'https://' : 'http://' ) . substr($path, 4);
        }

        $uri = $this->uriFactory->create(['uri' => $path]);

        if ($uri->getHost() !== '') {
            if ($parameters === [] || $uri->getQuery() !== '') {
                return $path;
            }

            return (string) $uri->withQuery(http_build_query($parameters));
        }

        if ($path === '/' && $parameters !== []) {
            // Magewire requires the framework, so avoid a dependency on Magento_Store for the scope constant.
            $path = (string) $this->scopeConfig->getValue('web/default/front', 'store');
        }

        return $this->route($path === '/' ? $path : ltrim($path, '/'), $parameters);
    }

    /**
     * Returns the path, query and fragment without the URL's authority.
     */
    public function relative(string $url): string
    {
        $uri = $this->uriFactory->create(['uri' => $url]);

        return (string) $uri
            ->withScheme('')
            ->withUserInfo('')
            ->withHost('')
            ->withPort(null)
            ->withPath($uri->getPath() === '' ? '/' : $uri->getPath());
    }
}
