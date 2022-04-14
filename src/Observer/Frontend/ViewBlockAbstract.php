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
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Model\ComponentManager;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\RenderLifecycle;
use Magewirephp\Magewire\Model\TreeMaster;

class ViewBlockAbstract
{
    protected ApplicationState $applicationState;
    protected ComponentHelper $componentHelper;
    protected ComponentManager $componentManager;
    protected HttpFactory $httpFactory;
    protected RenderLifecycle $renderLifecycle;

    /**
     * @param ApplicationState $applicationState
     * @param ComponentManager $componentManager
     * @param ComponentHelper $componentHelper
     * @param HttpFactory $httpFactory
     * @param RenderLifecycle $renderLifecycle
     */
    public function __construct(
        ApplicationState $applicationState,
        ComponentManager $componentManager,
        ComponentHelper $componentHelper,
        HttpFactory $httpFactory,
        RenderLifecycle $renderLifecycle
    ) {
        $this->applicationState = $applicationState;
        $this->componentHelper = $componentHelper;
        $this->componentManager = $componentManager;
        $this->httpFactory = $httpFactory;
        $this->renderLifecycle = $renderLifecycle;
    }

    /**
     * @return ComponentHelper
     */
    public function getComponentHelper(): ComponentHelper
    {
        return $this->componentHelper;
    }

    /**
     * @return ComponentManager
     */
    public function getComponentManager(): ComponentManager
    {
        return $this->componentManager;
    }

    /**
     * @return HttpFactory
     */
    public function getHttpFactory(): HttpFactory
    {
        return $this->httpFactory;
    }

    /**
     * @return RenderLifecycle
     */
    public function getRenderLifecycle(): RenderLifecycle
    {
        return $this->renderLifecycle;
    }

    /**
     * @param Template $block
     * @param Exception $exception
     * @return Template
     * @throws Exception // when on a subsequent update request.
     */
    public function transformToExceptionBlock(Template $block, Exception $exception): Template
    {
        $magewire = $block->getMagewire();

        /*
         * Just throw the exception went the request is in a subsequent state. This exception
         * will get caught within the controller action who will respond and show the
         * user a modal with the current given exception.
         */
        if ($magewire->getRequest() && $magewire->getRequest()->isSubsequent()) {
            throw $exception;
        }

        /*
         * In this stage, we know the page is in a preceding state, which means it on a
         * regular page load where it just needs to grep the block and change it's template
         * into the default Magewire exception template.
         */
        $block->unsetData('magewire');

        $block->setTemplate('Magewirephp_Magewire::component/exception.phtml');
        $block->setException($exception);
        $block->setApplicationState($this->applicationState);

        return $block;
    }
}
