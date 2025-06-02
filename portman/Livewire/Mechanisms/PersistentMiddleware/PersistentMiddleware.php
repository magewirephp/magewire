<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\PersistentMiddleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Magento\Framework\App\Request\Http as Request;
use function Magewirephp\Magewire\on;

class PersistentMiddleware extends \Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware
{
    protected static $persistentMiddleware = [
        //EnsureFrontendRequestsAreStateful::class,
        //AuthenticateSession::class,
        AuthenticateWithBasicAuth::class,
        SubstituteBindings::class,
        //RedirectIfAuthenticated::class,
        Authenticate::class,
        Authorize::class,
        //\App\Http\Middleware\Authenticate::class
    ];

    function __construct(
        private readonly Request $request
    ) {
        //
    }

    function boot()
    {
        on('dehydrate', function ($component, $context) {
            [$path, $method] = $this->extractPathAndMethodFromRequest();

            $context->addMemo('path', $path);
            // Although it's a POST request (stored in $method), Livewire still requires a GET value.
            $context->addMemo('method', 'GET');
        });

        on('flush-state', function() {
            // Only flush these at the end of a full request, so that child components have access to this data.
            $this->path = null;
            $this->method = null;
        });
    }

    protected function extractPathAndMethodFromRequest()
    {
        return [$this->request->getBasePath(), $this->request->getMethod()];
    }
}
