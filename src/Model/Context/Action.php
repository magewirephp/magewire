<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Context;

use Magewirephp\Magewire\Model\Action\CallMethod;
use Magewirephp\Magewire\Model\Action\FireEvent;
use Magewirephp\Magewire\Model\Action\SyncInput;

class Action
{
    protected CallMethod $callMethodAction;
    protected FireEvent $fireEventAction;
    protected SyncInput $syncInputAction;

    /**
     * @param CallMethod $callMethodAction
     * @param FireEvent $fireEventAction
     * @param SyncInput $syncInputAction
     */
    public function __construct(
        CallMethod $callMethodAction,
        FireEvent $fireEventAction,
        SyncInput $syncInputAction
    ) {
        $this->callMethodAction = $callMethodAction;
        $this->fireEventAction = $fireEventAction;
        $this->syncInputAction = $syncInputAction;
    }

    /**
     * @return CallMethod
     */
    public function getCallMethodAction(): CallMethod
    {
        return $this->callMethodAction;
    }

    /**
     * @return FireEvent
     */
    public function getFireEventAction(): FireEvent
    {
        return $this->fireEventAction;
    }

    /**
     * @return SyncInput
     */
    public function getSyncInputAction(): SyncInput
    {
        return $this->syncInputAction;
    }
}
