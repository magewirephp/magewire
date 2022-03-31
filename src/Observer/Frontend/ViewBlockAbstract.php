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

class ViewBlockAbstract
{
    protected ApplicationState $applicationState;
    protected ComponentHelper $componentHelper;
    protected ComponentManager $componentManager;
    protected HttpFactory $httpFactory;

    /**
     * ViewBlockAbstract constructor.
     * @param ApplicationState $applicationState
     * @param ComponentManager $componentManager
     * @param ComponentHelper $componentHelper
     * @param HttpFactory $httpFactory
     */
    public function __construct(
        ApplicationState $applicationState,
        ComponentManager $componentManager,
        ComponentHelper $componentHelper,
        HttpFactory $httpFactory
    ) {
        $this->applicationState = $applicationState;
        $this->componentHelper = $componentHelper;
        $this->componentManager = $componentManager;
        $this->httpFactory = $httpFactory;
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
     * Handle both preceding and subsequent request exceptions.
     *
     * @param Template $block
     * @param Exception $exception
     * @throws Exception
     */
    public function throwException(Template $block, Exception $exception): void
    {
        $magewire = $block->getMagewire();

        if ($magewire->getRequest() && $magewire->getRequest()->isSubsequent()) {
            throw $exception;
        }

        // Detach the component who's given the problems
        $block->unsetData('magewire');

        $block->setTemplate('Magewirephp_Magewire::component/exception.phtml');
        $block->setException($exception);
        $block->setApplicationState($this->applicationState);
    }
}
