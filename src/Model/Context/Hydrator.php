<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Context;

use Magewirephp\Magewire\Model\Hydrator\Children;
use Magewirephp\Magewire\Model\Hydrator\FormKey;
use Magewirephp\Magewire\Model\Hydrator\Hash;
use Magewirephp\Magewire\Model\Hydrator\BrowserEvent;
use Magewirephp\Magewire\Model\Hydrator\Emit;
use Magewirephp\Magewire\Model\Hydrator\Error;
use Magewirephp\Magewire\Model\Hydrator\FlashMessage;
use Magewirephp\Magewire\Model\Hydrator\Listener;
use Magewirephp\Magewire\Model\Hydrator\PostDeployment;
use Magewirephp\Magewire\Model\Hydrator\Property;
use Magewirephp\Magewire\Model\Hydrator\QueryString;
use Magewirephp\Magewire\Model\Hydrator\Redirect;
use Magewirephp\Magewire\Model\Hydrator\Resolver;
use Magewirephp\Magewire\Model\Hydrator\Security;
use Magewirephp\Magewire\Model\Hydrator\Loader;

class Hydrator
{
    protected Hash $hashHydrator;
    protected Listener $listenerHydrator;
    protected Emit $emit;
    protected BrowserEvent $browserEventHydrator;
    protected Property $propertyHydrator;
    protected QueryString $queryStringHydrator;
    protected Error $errorHydrator;
    protected Redirect $redirectHydrator;
    protected FlashMessage $flashMessageHydrator;
    protected Security $securityHydrator;
    protected Loader $loaderHydrator;
    protected PostDeployment $postDeploymentHydrator;
    protected FormKey $formKeyHydrator;
    protected Children $childrenHydrator;
    protected Resolver $resolverHydrator;

    public function __construct(
        Hash $hashHydrator,
        Listener $listenerHydrator,
        Emit $emit,
        BrowserEvent $browserEventHydrator,
        FlashMessage $flashMessageHydrator,
        Property $propertyHydrator,
        QueryString $queryStringHydrator,
        Error $errorHydrator,
        Redirect $redirectHydrator,
        Security $securityHydrator,
        Loader $loaderHydrator,
        PostDeployment $postDeploymentHydrator,
        FormKey $formKeyHydrator,
        Children $childrenHydrator,
        Resolver $resolverHydrator
    ) {
        $this->hashHydrator = $hashHydrator;
        $this->listenerHydrator = $listenerHydrator;
        $this->emit = $emit;
        $this->propertyHydrator = $propertyHydrator;
        $this->queryStringHydrator = $queryStringHydrator;
        $this->errorHydrator = $errorHydrator;
        $this->redirectHydrator = $redirectHydrator;
        $this->flashMessageHydrator = $flashMessageHydrator;
        $this->browserEventHydrator = $browserEventHydrator;
        $this->securityHydrator = $securityHydrator;
        $this->loaderHydrator = $loaderHydrator;
        $this->postDeploymentHydrator = $postDeploymentHydrator;
        $this->formKeyHydrator = $formKeyHydrator;
        $this->childrenHydrator = $childrenHydrator;
        $this->resolverHydrator = $resolverHydrator;
    }

    public function getHashHydrator(): Hash
    {
        return $this->hashHydrator;
    }

    public function getListenerHydrator(): Listener
    {
        return $this->listenerHydrator;
    }

    public function getEmitHydrator(): Emit
    {
        return $this->emit;
    }

    public function getBrowserEventHydrator(): BrowserEvent
    {
        return $this->browserEventHydrator;
    }

    public function getQueryStringHydrator(): QueryString
    {
        return $this->queryStringHydrator;
    }

    public function getPropertyHydrator(): Property
    {
        return $this->propertyHydrator;
    }

    public function getErrorHydrator(): Error
    {
        return $this->errorHydrator;
    }

    public function getRedirectHydrator(): Redirect
    {
        return $this->redirectHydrator;
    }

    public function getFlashMessageHydrator(): FlashMessage
    {
        return $this->flashMessageHydrator;
    }

    public function getSecurityHydrator(): Security
    {
        return $this->securityHydrator;
    }

    public function getLoaderHydrator(): Loader
    {
        return $this->loaderHydrator;
    }

    public function getPostDeploymentHydrator(): PostDeployment
    {
        return $this->postDeploymentHydrator;
    }

    public function getFormKeyHydrator(): FormKey
    {
        return $this->formKeyHydrator;
    }

    public function getChildrenHydrator(): Children
    {
        return $this->childrenHydrator;
    }

    public function getResolverHydrator(): Resolver
    {
        return $this->resolverHydrator;
    }
}
