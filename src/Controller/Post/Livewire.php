<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Controller\Post;

use Exception;
use Laminas\Http\AbstractMessage;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\ComponentResolver;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Exception\LifecycleException;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Model\HttpFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Livewire implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const HANDLE = 'magewire_post_livewire';

    protected ComponentHelper $componentHelper;
    protected SerializerInterface $serializer;
    protected HttpFactory $httpFactory;
    protected JsonFactory $resultJsonFactory;
    protected SecurityHelper $securityHelper;
    protected RequestInterface $request;
    protected LoggerInterface $logger;
    protected MagewireViewModel $magewireViewModel;
    protected ComponentResolver $componentResolver;

    public function __construct(
        JsonFactory $resultJsonFactory,
        ComponentHelper $componentHelper,
        SerializerInterface $serializer,
        HttpFactory $httpFactory,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger,
        MagewireViewModel $magewireViewModel,
        ComponentResolver $componentResolver
    ) {
        $this->componentHelper = $componentHelper;
        $this->serializer = $serializer;
        $this->httpFactory = $httpFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->securityHelper = $securityHelper;
        $this->request = $request;
        $this->logger = $logger;
        $this->magewireViewModel = $magewireViewModel;
        $this->componentResolver = $componentResolver;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $this->validateForUpdateRequest();

            $post = $this->serializer->unserialize(file_get_contents('php://input'));

            $component = $this->locateWireComponent($post);
            $component->setRequest($this->httpFactory->createRequest($post)->isSubsequent(true));

            $html = $component->getParent()->toHtml();
            $response = $component->getResponse();

            if ($response === null) {
                throw new LifecycleException(__('Response object not found for component'));
            }

            // Set final HTML for response.
            $response->effects['html'] = $html;

            return $result->setData([
                'effects' => $response->getEffects(),
                'serverMemo' => $response->getServerMemo()
            ]);
        } catch (Exception $exception) {
            $code = $exception instanceof HttpException ? $exception->getStatusCode() : $exception->getCode();
            $statuses = $this->getHttpResponseStatuses();

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
                'message' => $exception->getMessage(),
                'code' => $code
            ]);
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function locateWireComponent(array $post): Component
    {
        $resolver = $post['fingerprint']['resolver'] ?? null;

        if ($resolver) {
            return $this->componentResolver->get($post['fingerprint']['resolver'])->reconstruct($post);
        }

        throw new NotFoundException(
            __('Component resolver could not be found.')
        );
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * @throws LocalizedException
     */
    public function validateForUpdateRequest(): void
    {
        if ($this->securityHelper->validateFormKey($this->request) === false) {
            throw new HttpException(419, 'Form key expired. Please refresh and try again.');
        }
    }

    public function getHttpResponseStatuses(): array
    {
        $statuses = Response::$statusTexts;
        $statuses[419] = 'Form key expired';

        return $statuses;
    }
}
