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
use Magewirephp\Magewire\Exception\ComponentHydrationException;
use Magewirephp\Magewire\Model\Element\Event;
use Magewirephp\Magewire\Model\Event\EmitMetaData;
use Magewirephp\Magewire\Model\Event\EmitMetaDataFactory;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;
use Psr\Log\LoggerInterface;

class Emit implements HydratorInterface
{
    protected EventManagerInterface $eventManager;
    protected LoggerInterface $logger;

    private EmitMetaData $emitMetaData;

    public function __construct(
        EventManagerInterface $eventManager,
        LoggerInterface $logger,
        EmitMetaDataFactory $emitMetaDataFactory
    ) {
        $this->eventManager = $eventManager;
        $this->logger = $logger;

        // Just create an empty/reusable shell.
        $this->emitMetaData = $emitMetaDataFactory->create();
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
            try {
                $this->eventManager->dispatch('magewire_' . $data['event'], $this->computeEventData($event));
            } catch (ComponentHydrationException $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }

        return $this;
    }

    /**
     * Compute event data merged with addition event meta data.
     *
     * @throws ComponentHydrationException
     */
    protected function computeEventData(Event $event): array
    {
        $params = $event->getParams();

        if (isset($params['meta_data'])) {
            throw new ComponentHydrationException(
                __('Data key "meta_data" is reserved and therefore cannot be overwritten.')
            );
        }

        return $event->getParams() + [
            'meta_data' => $this->emitMetaData->setData([
                Event::KEY_ANCESTORS_ONLY => $event->isAncestorsOnly(),
                Event::KEY_SELF_ONLY => $event->isSelfOnly(),
                Event::KEY_TO => $event->getToComponent()
            ])
        ];
    }
}
