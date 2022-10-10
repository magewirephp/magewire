<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Observer\Frontend;

use Exception;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Model\ComponentManager;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\LayoutRenderLifecycle;

class ViewBlockAbstractToHtmlBefore extends ViewBlockAbstract implements ObserverInterface
{
    protected ?string $updateHandle = null;

    /**
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var Template $block */
        $block = $observer->getBlock();

        if ($block->hasData('magewire')) {
            try {
                $block->setData('magewire', $this->processMagewireBlock($block));
            } catch (Exception $exception) {
                $observer->setBlock($this->transformToExceptionBlock($block, $exception));
            }
        }
    }

    /**
     * @throws ComponentActionException
     * @throws MissingComponentException
     * @throws LocalizedException
     */
    public function processMagewireBlock($block): Component
    {
        $component = $this->getComponentHelper()->extractComponentFromBlock($block, true);
        $this->getLayoutRenderLifecycle()->start($block->getNameInLayout());

        $request = $component->getRequest();
        $data = $this->getComponentHelper()->extractDataFromBlock($block);

        if ($request && $this->getLayoutRenderLifecycle()->isParent($block->getNameInLayout())) {
            $this->setLayoutUpdateHandle($request->getFingerprint('handle'));
        }

        if ($request === null) {
            $request = $this->getComponentManager()->createInitialRequest(
                $block,
                $component,
                $data,
                $this->getLayoutUpdateHandle()
            )->isSubsequent(false);
        }

        $component->setRequest($request);

        /**
         * @lifecycle Runs on every request, immediately after the component is instantiated, but before
         * any other lifecycle methods are called.
         */
        try {
            $component->boot($data, $request);
        } catch (Exception $exception) {
            $this->logger->critical('Magewire: ' . $exception->getMessage());
        }

        if ($request->isPreceding()) {
            /**
             * @lifecycle Runs once, immediately after the component is instantiated, but before render()
             * is called. This is only called once on initial page load and never called again, even on
             * component refreshes.
             */
            try {
                $component->mount($data, $request);
            } catch (Exception $exception) {
                $this->logger->critical('Magewire: ' . $exception->getMessage());
            }
        }

        $this->getComponentManager()->hydrate($component);

        if ($request->isSubsequent()) {
            /**
             * @lifecycle Runs on every subsequent request, after the component is hydrated, but before
             * an action is performed or rendering.
             */
            $component->hydrate();
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

        if ($component->hasRequest('updates')) {
            $this->getComponentManager()->processUpdates($component, $request->getUpdates());
        }

        $component->setResponse($this->getHttpFactory()->createResponse($request));
        return $component;
    }

    /**
     * @param string $handle
     * @return string
     */
    public function setLayoutUpdateHandle(string $handle): string
    {
        return $this->updateHandle = $handle;
    }

    /**
     * @return string|null
     */
    public function getLayoutUpdateHandle(): ?string
    {
        return $this->updateHandle;
    }
}
