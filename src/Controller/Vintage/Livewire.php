<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Controller\Vintage;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magewirephp\Magewire\Controller\Post\Livewire as Origin;

class Livewire extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var Origin $origin */
    protected $origin;

    /**
     * @param Context $context
     * @param Origin $origin
     */
    public function __construct(
        Context $context,
        Origin $origin
    ) {
        parent::__construct($context);

        $this->origin = $origin;
    }

    public function execute(): Json
    {
        return $this->origin->execute();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return $this->origin->createCsrfValidationException($request);
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $this->origin->validateForCsrf($request);
    }
}
