<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller\Playwright;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magewirephp\Magewire\Controller\MagewireDeveloperAction;

class Exceptions extends MagewireDeveloperAction implements HttpGetActionInterface, HttpPostActionInterface
{
    protected string $pageTitle = 'Magewire / Playwright / Exceptions';

    public function __construct(
        PageFactory $pageFactory,
        ForwardFactory $resultForwardFactory,
        ApplicationState $applicationState,
        private readonly HttpRequest $request,
        private readonly ManagerInterface $messageManager,
        private readonly JsonFactory $jsonFactory
    ) {
        parent::__construct($pageFactory, $resultForwardFactory, $applicationState);
    }

    public function execute()
    {
        $result = parent::execute();

        // The inherited production guard must run before queuing any test messages.
        if ($result instanceof Page && $this->request->isPost()) {
            $this->messageManager->addComplexSuccessMessage('addCartSuccessMessage', [
                'product_name' => 'Magewire complex message regression',
                'cart_url' => '/checkout/cart/'
            ]);

            // Magento skips message rendering for Json results, leaving it for the next page.
            return $this->jsonFactory->create()->setData(['queued' => true]);
        }

        return $result;
    }
}
