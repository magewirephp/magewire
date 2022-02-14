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
use Magewirephp\Magewire\Exception\SubsequentRequestException;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Model\ComponentManager;
use Magewirephp\Magewire\Model\HttpFactory;

/**
 * Class ViewBlockAbstract
 * @package Magewirephp\Magewire\Observer\Frontend
 */
class ViewBlockAbstract
{
    /** @var ApplicationState $applicationState */
    private $applicationState;

    /** @var ComponentHelper $componentHelper */
    private $componentHelper;

    /** @var ComponentManager $componentManager */
    private $componentManager;

    /** @var HttpFactory $httpFactory */
    private $httpFactory;

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
     * @param Template $block
     * @param Exception $exception
     */
    public function throwException(Template $block, Exception $exception): void
    {
        try {
            $component = $this->componentHelper->extractComponentFromBlock($block);

            if ($component->getRequest() && $component->getRequest()->isSubsequent()) {
                throw new SubsequentRequestException($exception->getMessage());
            }
        } catch (Exception $exception) {
            // We accept the $exception at this stage.
        }

        // Detach the component who's given the problems.
        $block->unsetData('magewire');

        $block->setTemplate('Magewirephp_Magewire::component/exception.phtml');
        $block->setException($exception);
        $block->setApplicationState($this->applicationState);
    }
}
