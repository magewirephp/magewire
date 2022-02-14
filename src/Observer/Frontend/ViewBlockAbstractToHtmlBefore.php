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
use Magewirephp\Magewire\Exception\SubsequentRequestException;

/**
 * Class ViewBlockAbstractToHtmlBefore
 * @package Magewirephp\Magewire\Observer\Frontend
 */
class ViewBlockAbstractToHtmlBefore extends ViewBlockAbstract implements ObserverInterface
{
    /** @var $updateHandle null|string */
    protected $updateHandle;

    /**
     * @param Observer $observer
     * @throws SubsequentRequestException
     */
    public function execute(Observer $observer): void
    {
        /** @var Template $block */
        $block = $observer->getBlock();

        if ($block->hasMagewire()) {
            try {
                $component = $this->getComponentHelper()->extractComponentFromBlock($block);
                $component->setParent($this->determineTemplate($block));

                $request = $component->getRequest();

                $this->getComponentManager()->compose(
                    $component,
                    $this->getComponentHelper()->extractDataFromBlock($block)
                );

                // Fix for subsequent rendered wired children via e.g. a getChildHtml()
                if ($request !== null && $request->isSubsequent()) {
                    $this->overwriteUpdateHandle($request->getFingerprint('handle'));
                }
                if (($request === null) || ($request->isPreceding())) {
                    $request = $this->getComponentManager()->createInitialRequest(
                        $block,
                        $component,
                        $this->getUpdateHandle()
                    );
                }

                // Hydration lifecycle step
                $this->getComponentManager()->hydrate($component->setRequest($request));

                if ($component->hasRequest('updates')) {
                    $this->getComponentManager()->processUpdates($component, $request->getUpdates());
                }

                // Finalize component with its Response object
                $component->setResponse($this->getHttpFactory()->createResponse($component->getRequest()));

                // Re-attach the component onto the block
                $block->setData('magewire', $component);
            } catch (Exception $exception) {
                $this->throwException($block, $exception);
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
     * @return string|null
     */
    public function getUpdateHandle(): ?string
    {
        return $this->updateHandle;
    }
}
