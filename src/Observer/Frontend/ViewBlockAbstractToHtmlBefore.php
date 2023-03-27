<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Observer\Frontend;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Exception\MissingComponentException;

class ViewBlockAbstractToHtmlBefore extends ViewBlockAbstract implements ObserverInterface
{
    protected ?string $updateHandle = null;

    /**
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
        $component = $this->componentResolver->resolve($block);
        $this->getLayoutRenderLifecycle()->start($block->getNameInLayout());

        $request = $component->getRequest();
        $data = $this->getComponentHelper()->extractDataFromBlock($block);

        if ($request && $this->getLayoutRenderLifecycle()->isParent($block->getNameInLayout())) {
            $this->setLayoutUpdateHandle($request->getFingerprint('handle'));
        }

        if ($request === null) {
            $component->setRequest($this->getComponentManager()->createInitialRequest(
                $block,
                $component,
                $data,
                $this->getLayoutUpdateHandle()
            )->isSubsequent(false));
        }

        $this->getComponentManager()->hydrate($component);

        if ($component->hasRequest('updates')) {
            $component = $this->getComponentManager()->processUpdates($component, $component->getRequest('updates'));
        }

        $component->setResponse($this->getHttpFactory()->createResponse($component->getRequest()));
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
