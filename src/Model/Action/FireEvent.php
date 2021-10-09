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
use Magewirephp\Magewire\Helper\Functions as FunctionsHelper;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\ActionInterface;
use Magewirephp\Magewire\Model\Hydrator\Listener as ListenerHydrator;

/**
 * Class FireEvent
 * @package Magewirephp\Magewire\Model\Action
 */
class FireEvent implements ActionInterface
{
    /** @var FunctionsHelper $functionsHelper */
    private $functionsHelper;

    /** @var CallMethod $callMethodHandler */
    private $callMethodHandler;

    /** @var ListenerHydrator $listenerHydrator */
    private $listenerHydrator;

    /**
     * FireEvent constructor.
     * @param FunctionsHelper $functionsHelper
     * @param CallMethod $callMethodHandler
     * @param ListenerHydrator $listenerHydrator
     */
    public function __construct(
        FunctionsHelper $functionsHelper,
        CallMethod $callMethodHandler,
        ListenerHydrator $listenerHydrator
    ) {
        $this->functionsHelper = $functionsHelper;
        $this->callMethodHandler = $callMethodHandler;
        $this->listenerHydrator = $listenerHydrator;
    }

    /**
     * @inheritdoc
     *
     * @throws ComponentActionException|LocalizedException
     */
    public function handle(Component $component, array $payload)
    {
        $listeners  = $this->listenerHydrator->assimilateListeners($component);
        $method     = $listeners[$payload['event']] ?? false;
        $parameters = $payload['params'][0] ?? [];

        if ($method === false) {
            throw new ComponentActionException(__('Method %1 does not exist or can not be called', [$method]));
        }

        $this->callMethodHandler->handle($component, [
            'method' => $method,
            'params' => array_values($parameters)
        ]);
    }
}
