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
use Magento\Framework\Exception\NotFoundException;
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

    /**
     * @param JsonFactory $resultJsonFactory
     * @param ComponentHelper $componentHelper
     * @param PageFactory $resultPageFactory
     * @param SerializerInterface $serializer
     * @param HttpFactory $httpFactory
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        ComponentHelper $componentHelper,
        PageFactory $resultPageFactory,
        SerializerInterface $serializer,
        HttpFactory $httpFactory,
        EventManagerInterface $eventManager
    ) {
        $this->componentHelper = $componentHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $serializer;
        $this->httpFactory = $httpFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $post = $this->serializer->unserialize(file_get_contents('php://input'));
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
            $code = $exception->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : $exception->getCode();
            $phrase = Response::$statusTexts[$code] ?? Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];

            if ($exception instanceof LifecycleException) {
                $message = 'Something went wrong during the request lifecycle: ' . $exception->getMessage();
                $result->setStatusHeader($code, AbstractMessage::VERSION_11, $phrase);
            } elseif ($exception instanceof HttpException) {
                $result->setStatusHeader($exception->getStatusCode(), AbstractMessage::VERSION_11, $phrase);
            } else {
                $result->setStatusHeader($code, AbstractMessage::VERSION_11, $phrase);
            }

            return $result->setData([
                'message' => $message ?? $exception->getMessage(),
                'code' => $code
            ]);
        }
    }

    /**
     * @param array $post
     * @return BlockInterface
     * @throws NotFoundException
     */
    public function locateWireComponent(array $post): BlockInterface
    {
        $page = $this->resultPageFactory->create();
        $page->addHandle($post['fingerprint']['handle'])->initLayout();

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

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
