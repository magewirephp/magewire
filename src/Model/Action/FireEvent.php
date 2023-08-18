<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action;

use Magento\Framework\Exception\LocalizedException;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Action;
use Magewirephp\Magewire\Model\Hydrator\Listener as ListenerHydrator;

class FireEvent extends Action
{
    public const ACTION = 'fireEvent';

    protected CallMethod $callMethodHandler;
    protected ListenerHydrator $listenerHydrator;

    public function __construct(
        CallMethod $callMethodHandler,
        ListenerHydrator $listenerHydrator
    ) {
        $this->callMethodHandler = $callMethodHandler;
        $this->listenerHydrator = $listenerHydrator;
    }

    /**
     * @throws ComponentActionException
     * @throws LocalizedException
     */
    public function handle(Component $component, array $payload)
    {
        $listeners  = $this->listenerHydrator->assimilateListeners($component);
        $method     = $listeners[$payload['event']] ?? false;
        $parameters = is_array($payload['params']) && count($payload['params']) > 1
            ? $payload['params']
            : $payload['params'][0] ?? [];

        if ($method === false) {
            throw new ComponentActionException(__('Method does not exist or can not be called'));
        }

        $this->callMethodHandler->handle($component, [
            'method' => $method,
            'params' => $parameters
        ]);
    }
}
