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
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\ComponentManager;
use Magewirephp\Magewire\Model\ComponentResolver;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\LayoutRenderLifecycle;
use Psr\Log\LoggerInterface;

class ViewBlockAbstract
{
    protected ApplicationState $applicationState;
    protected ComponentManager $componentManager;
    protected ComponentResolver $componentResolver;
    protected HttpFactory $httpFactory;
    protected LayoutRenderLifecycle $layoutRenderLifecycle;
    protected LoggerInterface $logger;

    public function __construct(
        ApplicationState $applicationState,
        ComponentManager $componentManager,
        ComponentResolver $componentResolver,
        HttpFactory $httpFactory,
        LayoutRenderLifecycle $layoutRenderLifecycle,
        LoggerInterface $logger
    ) {
        $this->applicationState = $applicationState;
        $this->componentManager = $componentManager;
        $this->componentResolver = $componentResolver;
        $this->httpFactory = $httpFactory;
        $this->layoutRenderLifecycle = $layoutRenderLifecycle;
        $this->logger = $logger;
    }

    public function getComponentManager(): ComponentManager
    {
        return $this->componentManager;
    }

    public function getHttpFactory(): HttpFactory
    {
        return $this->httpFactory;
    }

    public function getLayoutRenderLifecycle(): LayoutRenderLifecycle
    {
        return $this->layoutRenderLifecycle;
    }

    public function getComponentResolver(): ComponentResolver
    {
        return $this->componentResolver;
    }

    /**
     * @throws Exception // when on a subsequent update request.
     */
    public function transformToExceptionBlock(Template $block, Exception $exception): Template
    {
        $magewire = $block->getData('magewire');

        /*
         * Just throw the exception went the request is in a subsequent state. This exception
         * will get caught within the controller action who will respond and show the
         * user a modal with the current given exception.
         */
        if ($magewire instanceof Component && $magewire->getRequest() && $magewire->getRequest()->isSubsequent()) {
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
