<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller;

use Magento\Framework\App\Router\Base;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

abstract class MagewireDeveloperAction
{
    protected string $pageTitle = 'Magewire / Developer / Action';

    private Page|null $page = null;

    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $resultForwardFactory,
        private readonly ApplicationState $applicationState
    ) {
        //
    }

    public function execute()
    {
        if ($this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION) {
            return $this->forward(Base::NO_ROUTE);
        }

        return $this->page();
    }

    protected function page(): Page
    {
        $page = $this->page ?? $this->pageFactory->create();
        $page->getConfig()->getTitle()->set($this->pageTitle);

        return $page;
    }

    protected function forward(string $action): Forward
    {
        return $this->resultForwardFactory->create()->forward($action);
    }
}
