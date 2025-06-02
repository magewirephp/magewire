<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\App\Router;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\RequestInterface;

class MagewireRouteValidator
{
    public function __construct(
        private readonly FrontNameResolver $frontNameResolver
    ) {
        //
    }

    /**
     * Validates whether the given request is a Magewire frontend or backend request.
     *
     * @tddo: This route validator currently checks whether a Magewire route — either with
     *        or without a custom admin front name — exists anywhere in the URL.
     *
     *        While this broad check works for now, it should be refined in the future to more
     *        precisely detect {route/controller/action} URIs, accounting for various possible
     *        prefixes or path structures.
     */
    public function validate(RequestInterface $request): bool
    {
        $path = '/magewire/update';

        if ($this->isAdminRequest($request)) {
            $path = '/' . $this->frontNameResolver->getFrontName() . $path;
        }

        return $this->isMagewireUri($path, $request);
    }

    private function isAdminRequest(RequestInterface $request): bool
    {
        if (preg_match('#/([^/]+)/magewire/update#', $request->getPathInfo(), $matches)) {
            return $matches[1] === $this->frontNameResolver->getFrontName();
        }

        return false;
    }

    private function isMagewireUri(string $path, RequestInterface $request): bool
    {
        return str_contains($request->getPathInfo(), $path);
    }
}
