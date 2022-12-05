<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Controller\Post\Upload;

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
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\Upload\Adapter\Local as UploadAdapter;
use Magento\Framework\App\Response\Http\FileFactory;

class Local implements HttpPostActionInterface, CsrfAwareActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected RequestInterface $request;
    protected SecurityHelper $securityHelper;
    protected Filesystem $fileSystem;
    protected FileFactory $fileFactory;
    protected UploaderFactory $fileUploaderFactory;
    protected Random $randomizer;
    protected UploadAdapter $uploadAdapter;
    protected DateTime $dateTime;

    public function __construct(
        JsonFactory $resultJsonFactory,
        SecurityHelper $securityHelper,
        Filesystem $fileSystem,
        UploaderFactory $fileUploaderFactory,
        Random $randomizer,
        UploadAdapter $uploadAdapter,
        DateTime $dateTime
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->securityHelper = $securityHelper;
        $this->fileSystem = $fileSystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->randomizer = $randomizer;
        $this->uploadAdapter = $uploadAdapter;
        $this->dateTime = $dateTime;
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function execute(): Json
    {
        // CLEAN UP THE TMP DIR FIRST...
        $result = $this->resultJsonFactory->create();

        if (! $this->uploadAdapter->hasCorrectSignature() || $this->uploadAdapter->signatureHasNotExpired()) {
            return $result->setStatusHeader(401);
        }

        try {
            $target = $this->fileUploaderFactory->create(['fileId' => 'files[0]']);

            $target->setAllowedExtensions(['jpeg']);
            $target->setAllowCreateFolders(false);
            $target->setAllowRenameFiles(true);
            $target->setFilenamesCaseSensitivity(false);

            $target->validateFile();

            $fileDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
            $file = $this->randomizer->getUniqueHash() . '.' . $target->getFileExtension();

            $target->save($fileDirectory->getAbsolutePath('magewire'), $file);
        } catch (LocalizedException | FileSystemException | Exception $exception) {
            return $result->setData([
                'message' => $exception->getMessage(),
                'code' => 422
            ]);
        }

        return $result->setData(['paths' => [$target->getUploadedFileName()]]);
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
