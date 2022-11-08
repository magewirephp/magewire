<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Laminas\Uri\UriFactory;
use Magento\Framework\Url\HostChecker;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Redirect implements HydratorInterface
{
    protected UrlInterface $builder;
    protected HostChecker $hostChecker;

    /**
     * @param UrlInterface $builder
     * @param HostChecker $hostChecker
     */
    public function __construct(
        UrlInterface $builder,
        HostChecker $hostChecker
    ) {
        $this->builder = $builder;
        $this->hostChecker = $hostChecker;
    }

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        //
    }

    /**
     * A redirect can both be set on the initial and/or subsequent request.
     *
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        $redirect = $component->getRedirect();

        if ($redirect === null) {
            return;
        }

        $url = $redirect->getUrl();

        if (strncmp('www.', $url, 4) === 0) {
            $url = ($redirect->isSecure() ? 'https://' : 'http://') . str_replace('www.', '', $url);
        }

        $parse = UriFactory::factory($url);

        if ($this->hostChecker->isOwnOrigin($parse->toString())) {
            $parse = UriFactory::factory(
                $this->builder->getUrl(ltrim($url, '\/'), $redirect->hasParams() ? $redirect->getParams() : null)
            );
        } elseif ($redirect->hasParams() && count($parse->getQueryAsArray()) === 0) {
            $parse->setQuery($redirect->getParams());
        }

        $response->effects['redirect'] = $parse->toString();
    }
}
