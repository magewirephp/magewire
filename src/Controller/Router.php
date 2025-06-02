<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magewirephp\Magento\App\Router\MagewireRouteValidator;

class Router implements RouterInterface
{
    /**
     * @param MagewireUpdateRoute[] $routes
     */
    public function __construct(
        private readonly MagewireRouteValidator $magewireRouteValidator,
        private readonly array $routes = []
    ) {
        //
    }

    public function match(RequestInterface $request)
    {
        if ($this->matchesMagewireSpecifics($request)) {
            foreach (array_filter($this->routes) as $route) {
                if ($result = $route->match($request)) {
                    return $result;
                }
            }
        }

        return null;
    }

    private function matchesMagewireSpecifics(RequestInterface $request): bool
    {
        return $request instanceof Request && $this->magewireRouteValidator->validate($request);
    }
}
