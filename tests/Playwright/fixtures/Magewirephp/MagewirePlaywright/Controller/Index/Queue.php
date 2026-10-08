<?php

declare(strict_types=1);

namespace Magewirephp\MagewirePlaywright\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;

class Queue implements HttpPostActionInterface
{
    public function __construct(
        private readonly ManagerInterface $messageManager,
        private readonly JsonFactory $jsonFactory
    ) {
    }

    public function execute(): Json
    {
        $this->messageManager->addComplexSuccessMessage('magewirePlaywrightComplexMessage', [
            'text' => 'Complex flash message rendered after a JSON request.'
        ]);

        // Magento's MessagePlugin skips Json results, leaving this message for the next page.
        return $this->jsonFactory->create()->setData(['queued' => true]);
    }
}
