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
use Laminas\Http\Response;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\Helper\Security;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Exception\MagewireException;
use Magewirephp\Magewire\Exception\SubsequentRequestException;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magento\Framework\View\Result\PageFactory;
use Magewirephp\Magewire\Model\HttpFactory;

class Livewire implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const HANDLE = 'magewire_post_livewire';

    protected FormKey $formKey;
    protected ComponentHelper $componentHelper;
    protected PageFactory $resultPageFactory;
    protected SerializerInterface $serializer;
    protected HttpFactory $httpFactory;
    protected JsonFactory $resultJsonFactory;
    protected EventManagerInterface $eventManager;

    /**
     * @param FormKey $formKey
     * @param JsonFactory $resultJsonFactory
     * @param ComponentHelper $componentHelper
     * @param PageFactory $resultPageFactory
     * @param SerializerInterface $serializer
     * @param HttpFactory $httpFactory
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        FormKey $formKey,
        JsonFactory $resultJsonFactory,
        ComponentHelper $componentHelper,
        PageFactory $resultPageFactory,
        SerializerInterface $serializer,
        HttpFactory $httpFactory,
        EventManagerInterface $eventManager
    ) {
        $this->formKey = $formKey;
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
                throw new MagewireException(__('Response object not found for component'));
            }

            // Set final HTML for response.
            $response->effects['html'] = $html;

            return $result->setData([
                'effects' => $response->getEffects(),
                'serverMemo' => $response->getServerMemo()
            ]);
        } catch (SubsequentRequestException $exception) {
            $result->setStatusHeader(Response::STATUS_CODE_500, AbstractMessage::VERSION_11, 'Bad Request');

            return $result->setData([
                'message' => 'Something went wrong during the subsequent request lifecycle: ' . $exception->getMessage(),
                'code' => $exception->getCode()
            ]);
        } catch (Exception $exception) {
            $result->setStatusHeader(Response::STATUS_CODE_500, AbstractMessage::VERSION_11, 'Bad Request');

            return $result->setData([
                'message' => 'Something went wrong outside the component: ' . $exception->getMessage(),
                'code' => $exception->getCode()
            ]);
        }
    }

    /**
     * @param array $post
     * @return BlockInterface
     * @throws SubsequentRequestException
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
            throw new SubsequentRequestException('Magewire component does not exist');
        }

        return $block;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost() && Security::compareStrings($request->getHeader('X-CSRF-TOKEN'), $this->formKey->getFormKey());
    }
}
