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
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Exception\LifecycleException;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magento\Framework\View\Result\PageFactory;
use Magewirephp\Magewire\Model\HttpFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Livewire implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const HANDLE = 'magewire_post_livewire';

    protected ComponentHelper $componentHelper;
    protected PageFactory $resultPageFactory;
    protected SerializerInterface $serializer;
    protected HttpFactory $httpFactory;
    protected JsonFactory $resultJsonFactory;
    protected EventManagerInterface $eventManager;
    protected SecurityHelper $securityHelper;
    protected RequestInterface $request;
    protected LoggerInterface $logger;

    public function __construct(
        JsonFactory $resultJsonFactory,
        ComponentHelper $componentHelper,
        PageFactory $resultPageFactory,
        SerializerInterface $serializer,
        HttpFactory $httpFactory,
        EventManagerInterface $eventManager,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->componentHelper = $componentHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $serializer;
        $this->httpFactory = $httpFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eventManager = $eventManager;
        $this->securityHelper = $securityHelper;
        $this->request = $request;
        $this->logger = $logger;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $this->validateForUpdateRequest();

            $post = $this->serializer->unserialize(file_get_contents('php://input'));
            /** @var Template $block */
            $block = $this->locateWireComponent($post);

            $component = $this->componentHelper->extractComponentFromBlock($block);
            $component->setRequest($this->httpFactory->createRequest($post)->isSubsequent(true));

            $html = $block->toHtml();
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
     * @throws NotFoundException
     */
    public function locateWireComponent(array $post): BlockInterface
    {
        $page = $this->resultPageFactory->create();
        $page->addHandle(strtolower($post['fingerprint']['handle']))->initLayout();

        $this->eventManager->dispatch('locate_wire_component_before', [
            'post' => $post, 'page' => $page
        ]);

        $block = $page->getLayout()->getBlock($post['fingerprint']['name']);

        if ($block === false) {
            throw new NotFoundException(
                __('Magewire component "%1" could not be found', [$post['fingerprint']['name']])
            );
        }

        return $block;
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
