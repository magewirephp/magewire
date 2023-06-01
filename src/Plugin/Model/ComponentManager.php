<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Plugin\Model;

use Exception;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\Action\FireEvent;
use Magewirephp\Magewire\Model\ComponentManager as Subject;
use Magewirephp\Magewire\Model\Hydrator\Listener as ListenerHydrator;
use Psr\Log\LoggerInterface;

class ComponentManager
{
    protected LoggerInterface $logger;
    protected PropertyHelper $propertyHelper;
    protected ListenerHydrator $listenerHydrator;

    public function __construct(
        LoggerInterface $logger,
        PropertyHelper $propertyHelper,
        ListenerHydrator $listenerHydrator
    ) {
        $this->logger = $logger;
        $this->propertyHelper = $propertyHelper;
        $this->listenerHydrator = $listenerHydrator;
    }

    public function beforeHydrate(Subject $subject, Component $component): array
    {
        /**
         * @lifecycle Runs on every request, immediately after the component is instantiated, but before
         * any other lifecycle methods are called.
         */
        try {
            $component->boot();
        } catch (Exception $exception) {
            $this->logger->critical('Magewire: ' . $exception->getMessage());
        }

        /**
         * @lifecycle Runs once, immediately after the component is instantiated, but before render()
         * is called. This is only called once on initial page load and never called again, even on
         * component refreshes.
         */
        try {
            if ($component->getRequest()->isPreceding()) {
                $component->mount();
            }
        } catch (Exception $exception) {
            $this->logger->critical('Magewire: ' . $exception->getMessage());
        }

        /**
         * @lifecycle Mark the component request as refreshing in order to prevent weird
         * behaviour where a component is trying to refresh itself during a subsequent
         * request using the update server memo as it's state.
         */
        try {
            $component->getRequest()->isRefreshing($this->isComponentTryingToRefresh($component));
        } catch (Exception $exception) {
            $this->logger->critical('Magewire: ' . $exception->getMessage());
        }

        return [$component];
    }

    public function afterHydrate(Subject $subject, Component $component): Component
    {
        /**
         * @lifecycle Runs on every subsequent request, after the component is hydrated, but before
         * an action is performed or rendering.
         */
        try {
            if ($component->getRequest()->isSubsequent()) {
                $component->hydrate();
            }
        } catch (Exception $exception) {
            $this->logger->critical('Magewire: ' . $exception->getMessage());
        }

        /**
         * @lifecycle Runs on every request, after the component is mounted or hydrated, but before
         * any update methods are called.
         */
        try {
            $component->booted();
        } catch (Exception $exception) {
            $this->logger->critical('Magewire: ' . $exception->getMessage());
        }

        return $component;
    }

    public function isComponentTryingToRefresh(Component $component): bool
    {
        try {
            $updates = $component->getRequest('updates');

            if (is_array($updates)
                && count($updates) === 1
                && $updates[0]['type'] === FireEvent::ACTION
            ) {
                $listeners = $this->listenerHydrator->assimilateListeners($component);
                return ltrim($listeners[$updates[0]['payload']['event']], '$') === $component::REFRESH_METHOD;
            }
        } catch (Exception $exception) {
            $this->logger->critical(
                'Magewire: Could not determine if the component is trying to refresh: ' . $exception->getMessage()
            );
        }

        return false;
    }
}
