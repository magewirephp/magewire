<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Controller;

use Exception;
use Laminas\Http\AbstractMessage;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\CorruptPayloadException;
use Magewirephp\Magewire\Exception\LifecycleException;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\ComponentResolver;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\RequestInterface as MagewireRequestInterface;
use Magewirephp\Magewire\Model\Request\MagewireSubsequentActionInterface;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Post implements HttpPostActionInterface, CsrfAwareActionInterface, MagewireSubsequentActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected SecurityHelper $securityHelper;
    protected RequestInterface $request;
    protected LoggerInterface $logger;

    public function __construct(
        JsonFactory $resultJsonFactory,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->securityHelper = $securityHelper;
        $this->request = $request;
        $this->logger = $logger;
    }

    public function throwException(Exception $exception): Json
    {
        $result = $this->resultJsonFactory->create();
        $statuses = $this->getHttpResponseStatuses();

        $code = $exception instanceof HttpException ? $exception->getStatusCode() : $exception->getCode();
        $message = empty($exception->getMessage()) ? ($statuses[$code] ?? 'Something went wrong') : $exception->getMessage();

        // Make an exception for optional outsiders.
        $code = in_array($code, [0, -1], true) ? Response::HTTP_INTERNAL_SERVER_ERROR : $code;
        // Try and grep the status from the available stack or get 500 when it's unavailable.
        $code = $statuses[$code] ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
        // Set the status header with the returned code and belonging response phrase.
        $result->setStatusHeader($code, AbstractMessage::VERSION_11, $statuses[$code]);

        if ($code === 500) {
            $this->logger->critical($exception->getMessage());
        }

        return $result->setData([
            'message' => $message,
            'code' => $code
        ]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $message = 'Session expired. Please refresh and try again.';
        $result = $this->throwException(new HttpException(419, $message));

        return new InvalidRequestException($result, [new Phrase($message)]);
    }

    public function validateForCsrf(RequestInterface $request): bool
    {
        return $this->securityHelper->validateFormKey($this->request);
    }

    public function getHttpResponseStatuses(): array
    {
        $statuses = Response::$statusTexts;
        $statuses[419] = 'Session expired';

        return $statuses;
    }
}
