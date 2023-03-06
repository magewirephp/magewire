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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Model\Upload\File\TemporaryUploaderFactory as TemporaryFileUploaderFactory;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\Upload\Adapter\Local as UploadAdapter;
use Magento\Framework\App\Response\Http\FileFactory;
use Magewirephp\Magewire\Model\Upload\AdapterProvider;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;

class Upload implements HttpPostActionInterface, CsrfAwareActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected AdapterProvider $adapterProvider;
    protected RequestInterface $request;
    protected TemporaryFileUploaderFactory $temporaryFileUploaderFactory;

    public function __construct(
        JsonFactory $resultJsonFactory,
        AdapterProvider $adapterProvider,
        RequestInterface $request,
        TemporaryFileUploaderFactory $temporaryFileUploaderFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->adapterProvider = $adapterProvider;
        $this->request = $request;
        $this->temporaryFileUploaderFactory = $temporaryFileUploaderFactory;
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function execute(): Json
    {
        // CLEAN UP THE TMP DIR FIRST...
        $result = $this->resultJsonFactory->create();
        $adapter = $this->adapterProvider->getByName($this->request->getParam(UploadAdapterInterface::QUERY_PARAM_ADAPTER));

        if (! $adapter->hasCorrectSignature() || $adapter->signatureHasNotExpired()) {
            return $result->setStatusHeader(401);
        }

        try {
            $files = $this->request->getFiles('files', []);

            $targets = [];
            foreach (array_keys($files) as $fileKey) {
                $target = $this->temporaryFileUploaderFactory->create(['fileId' => 'files[' . $fileKey . ']']);

                $target->setAllowCreateFolders(false);
                $target->setAllowRenameFiles(true);
                $target->setFilenamesCaseSensitivity(false);

                $target->validateFile();

                $targets[] = $target;
            }

            $paths = $adapter->stash($targets);

            if ($paths === false) {
                throw new LocalizedException(__('Something went wrong.'));
            }

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

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }
}
