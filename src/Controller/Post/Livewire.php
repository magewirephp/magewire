<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Controller\Post;

use Exception;
use Laminas\Http\AbstractMessage;
use Magento\Framework\App\ObjectManager;
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

class Livewire implements HttpPostActionInterface, CsrfAwareActionInterface, MagewireSubsequentActionInterface
{
    public const HANDLE = 'magewire_post_livewire';

    protected HttpFactory $httpFactory;
    protected JsonFactory $resultJsonFactory;
    protected SecurityHelper $securityHelper;
    protected RequestInterface $request;
    protected LoggerInterface $logger;
    protected MagewireViewModel $magewireViewModel;
    protected ComponentResolver $componentResolver;
    protected SerializerInterface $serializer;

    public function __construct(
        JsonFactory $resultJsonFactory,
        SerializerInterface $serializer,
        HttpFactory $httpFactory,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger,
        MagewireViewModel $magewireViewModel,
        ComponentResolver $componentResolver
    ) {
        $this->httpFactory = $httpFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->securityHelper = $securityHelper;
        $this->request = $request;
        $this->logger = $logger;
        $this->magewireViewModel = $magewireViewModel;
        $this->componentResolver = $componentResolver;

        $this->serializer = $serializer
            ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    public function execute(): Json
    {
        try {
            try {
                $request = $this->httpFactory->createRequest($this->getRequestParams())->isSubsequent(true);
            } catch (LocalizedException $exception) {
                throw new HttpException(400);
            }

            $component = $this->locateWireComponent($request);
            $component->setRequest($request);

            $html = $component->getParent()->toHtml();
            $response = $component->getResponse();

            if ($response === null) {
                throw new LifecycleException(__('Response object not found for component'));
            }

            // Set final HTML for response.
            $response->effects['html'] = $html;
            // Prepare result object.
            $result = $this->resultJsonFactory->create();

            return $result->setData([
                'effects' => $response->getEffects(),
                'serverMemo' => $response->getServerMemo()
            ]);
        } catch (Exception $exception) {
            return $this->throwException($exception);
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    public function locateWireComponent(MagewireRequestInterface $request): Component
    {
        $resolver = $this->componentResolver->get($request->getFingerprint('resolver'));
        return $resolver->reconstruct($request)->setResolver($resolver);
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

    /**
     * Allows the Livewire Browser Plugin to modify component properties,
     * which triggers an AJAX call similar to the MageWire JS API's form.set('address.country_id', 'NL').
     * In such cases, the request parameters, including the fingerprint, are sent in a JSON body
     * instead of traditional request parameters.
     *
     * This method ensures compatibility by supporting both JSON body and request parameters,
     * enabling seamless interaction between the client and server.
     */
    private function getRequestParams(): array
    {
        $content = $this->request->getContent();

        if (! empty($content)) {
            return $this->serializer->unserialize($content);
        }

        return $this->request->getParams();
    }
}
