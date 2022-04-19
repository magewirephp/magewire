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
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Exception\MissingComponentException;

class ViewBlockAbstractToHtmlBefore extends ViewBlockAbstract implements ObserverInterface
{
    protected ?string $updateHandle = null;
    protected ?bool $isSubsequent = null;

    /**
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var Template $block */
        $block = $observer->getBlock();

        if ($block->hasMagewire()) {
            try {
                $component = $this->getComponentHelper()->extractComponentFromBlock($block, true);
                $this->getRenderLifecycle()->start($block->getNameInLayout());

                $request = $component->getRequest();
                $data = $this->getComponentHelper()->extractDataFromBlock($block);

                if ($this->getRenderLifecycle()->isParent($block->getNameInLayout())) {
                    if ($request) {
                        $this->overwriteUpdateHandle($request->getFingerprint('handle'));
                    }

                    $this->overwriteSubsequentState($request !== null);
                }

                if ($request === null) {
                    $request = $this->getComponentManager()->createInitialRequest(
                        $block,
                        $component,
                        $data,
                        $this->getUpdateHandle()
                    );
                }

                $request->isSubsequent($this->isSubsequent);

                $component->boot(...[$data, $request]);

                if ($request->isPreceding()) {
                    $component->mount(...[$data, $request]);
                }

                // Hydration lifecycle step.
                $this->getComponentManager()->hydrate($component->setRequest($request));

                if ($component->hasRequest('updates')) {
                    $this->getComponentManager()->processUpdates($component, $request->getUpdates());
                }

                $component->setResponse($this->getHttpFactory()->createResponse($component->getRequest()));
                $component->booted(...[$request]);

                // Re-attach the component onto the block.
                $block->setData('magewire', $component);
            } catch (Exception $exception) {
                $observer->setBlock($this->transformToExceptionBlock($block, $exception));
            }
        }
    }

    /**
     * Determines the template by a default template path
     * when the path is not defined within the layout.
     *
     * Results in: {Module_Name::magewire/dashed-class-name.phtml}
     *
     * @param Template $block
     * @return Template
     * @throws MissingComponentException
     */
    public function determineTemplate(Template $block): Template
    {
        if ($block->getTemplate() === null) {
            $magewire = $this->getComponentHelper()->extractComponentFromBlock($block);
            $module = explode('\\', get_class($magewire));

            $prefix = $module[0] . '_' . $module[1];
            $affix  = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', end($module)));

            $block->setTemplate($prefix . '::magewire/' . $affix . '.phtml');
        }

        return $block;
    }

    /**
     * @param string $handle
     * @return string
     */
    public function overwriteUpdateHandle(string $handle): string
    {
        return $this->updateHandle = $handle;
    }

    /**
     * @param bool $subsequent
     * @return bool
     */
    public function overwriteSubsequentState(bool $subsequent): bool
    {
        return $this->isSubsequent = $subsequent;
    }

    /**
     * @return string|null
     */
    public function getUpdateHandle(): ?string
    {
        return $this->updateHandle;
    }

    /**
     * @return bool|null
     */
    public function getSubsequentState(): ?bool
    {
        return $this->isSubsequent;
    }
}
