<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\PersistentMiddleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Magento\Framework\App\Request\Http as Request;
use function Magewirephp\Magewire\on;
use Illuminate\Routing\Router;
use Magewirephp\Magewire\Mechanisms\Mechanism;
use Illuminate\Support\Str;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Mechanisms\HandleRequests\HandleRequests;
class PersistentMiddleware extends Mechanism
{
    protected static $persistentMiddleware = [
        //EnsureFrontendRequestsAreStateful::class,
        //AuthenticateSession::class,
        AuthenticateWithBasicAuth::class,
        SubstituteBindings::class,
        //RedirectIfAuthenticated::class,
        Authenticate::class,
        Authorize::class,
    ];
    protected $path;
    protected $method;
    function boot()
    {
        on('dehydrate', function ($component, $context) {
            [$path, $method] = $this->extractPathAndMethodFromRequest();
            $context->addMemo('path', $path);
            // Although it's a POST request (stored in $method), Livewire still requires a GET value.
            $context->addMemo('method', 'GET');
        });
        on('flush-state', function () {
            // Only flush these at the end of a full request, so that child components have access to this data.
            $this->path = null;
            $this->method = null;
        });
    }
    function addPersistentMiddleware($middleware)
    {
        static::$persistentMiddleware = Router::uniqueMiddleware(array_merge(static::$persistentMiddleware, (array) $middleware));
    }
    function setPersistentMiddleware($middleware)
    {
        static::$persistentMiddleware = Router::uniqueMiddleware((array) $middleware);
    }
    function getPersistentMiddleware()
    {
        return static::$persistentMiddleware;
    }
    protected function extractPathAndMethodFromRequest()
    {
        return [$this->request->getBasePath(), $this->request->getMethod()];
    }
    protected function extractPathAndMethodFromSnapshot($snapshot)
    {
        if (!isset($snapshot['memo']['path']) || !isset($snapshot['memo']['method'])) {
            return;
        }
        // Store these locally, so dynamically added child components can use this data.
        $this->path = $snapshot['memo']['path'];
        $this->method = $snapshot['memo']['method'];
    }
    protected function applyPersistentMiddleware()
    {
        $request = $this->makeFakeRequest();
        $middleware = $this->getApplicablePersistentMiddleware($request);
        // Only send through pipeline if there are middleware found
        if (is_null($middleware)) {
            return;
        }
        Utils::applyMiddleware($request, $middleware);
    }
    protected function makeFakeRequest()
    {
        $originalPath = $this->formatPath($this->path);
        $originalMethod = $this->method;
        $currentPath = $this->formatPath(request()->path());
        // Clone server bag to ensure changes below don't overwrite the original.
        $serverBag = clone request()->server;
        // Replace the Livewire endpoint path with the path from the original request.
        $serverBag->set('REQUEST_URI', str_replace($currentPath, $originalPath, $serverBag->get('REQUEST_URI')));
        $serverBag->set('REQUEST_METHOD', $originalMethod);
        /**
         * Make the fake request from the current request with path and method changed so
         * all other request data, such as headers, are available in the fake request,
         * but merge in the new server bag with the updated `REQUEST_URI`.
         */
        $request = request()->duplicate(server: $serverBag->all());
        return $request;
    }
    protected function formatPath($path)
    {
        return '/' . ltrim($path, '/');
    }
    protected function getApplicablePersistentMiddleware($request)
    {
        $route = $this->getRouteFromRequest($request);
        if (!$route) {
            return [];
        }
        $middleware = app('router')->gatherRouteMiddleware($route);
        return $this->filterMiddlewareByPersistentMiddleware($middleware);
    }
    protected function getRouteFromRequest($request)
    {
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn() => $route);
        return $route;
    }
    protected function filterMiddlewareByPersistentMiddleware($middleware)
    {
        $middleware = collect($middleware);
        $persistentMiddleware = collect(app(PersistentMiddleware::class)->getPersistentMiddleware());
        return $middleware->filter(function ($value, $key) use ($persistentMiddleware) {
            return $persistentMiddleware->contains(function ($iValue, $iKey) use ($value) {
                // Some middlewares can be closures.
                if (!is_string($value)) {
                    return false;
                }
                // Ensure any middleware arguments aren't included in the comparison
                return Str::before($value, ':') == $iValue;
            });
        })->values()->all();
    }
    function __construct(private readonly Request $request)
    {
        //
    }
}