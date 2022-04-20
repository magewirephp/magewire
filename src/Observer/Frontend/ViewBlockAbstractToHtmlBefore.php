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
                $component->boot(...[$data, $component->getRequest()]);

                if ($request->isPreceding()) {
                    $component->mount(...[$data, $component->getRequest()]);
                }

                // Hydration lifecycle step.
                $this->getComponentManager()->hydrate($component);

                if ($component->hasRequest('updates')) {
                    $this->getComponentManager()->processUpdates($component, $component->getRequest()->getUpdates());
                }

                $component->setResponse($this->getHttpFactory()->createResponse($component->getRequest()));
                $component->booted(...[$component->getRequest()]);

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
