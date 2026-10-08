<?php

declare(strict_types=1);

namespace Magewirephp\MagewirePlaywright\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\State;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly State $state
    ) {
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setHeader('X-Magewire-Playwright-Mode', $this->state->getMode(), true);

        return $page;
    }
}
