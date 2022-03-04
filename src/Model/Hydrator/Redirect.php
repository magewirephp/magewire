<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Redirect implements HydratorInterface
{
    protected UrlInterface $builder;

    /**
     * Redirect constructor.
     * @param UrlInterface $builder
     */
    public function __construct(
        UrlInterface $builder
    ) {
        $this->builder = $builder;
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
        if ($redirect = $component->getRedirect()) {
            $url = $redirect->getUrl();

            if ($redirect->hasParams()) {
                $url = $this->builder->getUrl($url, $redirect->getParams());
            }

            $response->effects['redirect'] = $url;
        }
    }
}
