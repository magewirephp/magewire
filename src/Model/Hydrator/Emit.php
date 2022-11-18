<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Element\Event;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Emit implements HydratorInterface
{
    protected EventManagerInterface $eventManager;

    public function __construct(
        EventManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    // phpcs:ignore
    public function hydrate(Component $component, RequestInterface $request): void
    {
    }

    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        foreach ($component->getEventQueue() as $event) {
            $this->applyEmits($response, $event)->dispatchAsObservableEvent($event);
        }
    }

    public function applyEmits(ResponseInterface $response, Event $event): Emit
    {
        $response->effects['emits'][] = $event->serialize();
        return $this;
    }

    public function dispatchAsObservableEvent(Event $event): Emit
    {
        $data = $event->serialize();

        if (isset($data['event']) && ! empty($data['event'])) {
            $this->eventManager->dispatch('magewire_' . $data['event'], $event->getParams());
        }

        return $this;
    }
}
