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
use Magento\Framework\Phrase;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Controller\Post;
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

class Livewire extends Post
{
    public const HANDLE = 'magewire_post_livewire';

    protected HttpFactory $httpFactory;
    protected JsonFactory $resultJsonFactory;
    protected RequestInterface $request;
    protected MagewireViewModel $magewireViewModel;
    protected ComponentResolver $componentResolver;

    public function __construct(
        JsonFactory $resultJsonFactory,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        HttpFactory $httpFactory,
        MagewireViewModel $magewireViewModel,
        ComponentResolver $componentResolver
    ) {
        parent::__construct(
            $resultJsonFactory,
            $securityHelper,
            $request,
            $logger
        );

        $this->httpFactory = $httpFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->magewireViewModel = $magewireViewModel;
        $this->componentResolver = $componentResolver;
    }

    public function execute(): Json
    {
        try {
            try {
                $request = $this->httpFactory->createRequest($this->request->getParams())->isSubsequent(true);
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
}
