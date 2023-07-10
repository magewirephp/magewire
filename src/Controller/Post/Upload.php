<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Controller\Post;

use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Controller\Post;
use Magewirephp\Magewire\Exception\NoSuchUploadAdapterInterface;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\ComponentResolver;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\Request\MagewireSubsequentActionInterface;
use Magewirephp\Magewire\Model\Upload\AdapterProvider;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Upload extends Post
{
    protected AdapterProvider $adapterProvider;

    public function __construct(
        JsonFactory $resultJsonFactory,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger,
        AdapterProvider $adapterProvider
    ) {
        parent::__construct(
            $resultJsonFactory,
            $securityHelper,
            $request,
            $logger
        );

        $this->adapterProvider = $adapterProvider;
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException|NoSuchUploadAdapterInterface
     */
    public function execute(): Json
    {
        try {
            $adapter = $this->adapterProvider->getByAccessor(
                $this->request->getParam(UploadAdapterInterface::QUERY_PARAM_ADAPTER)
            );
        } catch (NoSuchUploadAdapterInterface $exception) {
            return $this->throwException(new HttpException(400, 'Bad Request'));
        }

        if (! $adapter->hasCorrectSignature()) {
            return $this->throwException(new HttpException(403, 'Incorrect Signature'));
        } elseif ($adapter->signatureHasExpired()) {
            return $this->throwException(new HttpException(403, 'Signature Expired'));
        }

        try {
            $paths = $adapter->stash($this->request->getFiles('files', []));
            $result = $this->resultJsonFactory->create();

            return $result->setData([
                'paths' => $paths
            ]);
        } catch (LocalizedException | FileSystemException | Exception $exception) {
            return $result->setData([
                'message' => $exception->getMessage(),
                'code' => 422
            ]);
        }
    }
}
