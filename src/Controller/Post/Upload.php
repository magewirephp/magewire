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
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Controller\Post;
use Magewirephp\Magewire\Exception\NoSuchUploadAdapterInterface;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\ComponentResolver;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\Request\MagewireSubsequentActionInterface;
use Magewirephp\Magewire\Model\Upload\TemporaryFileFactory;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Upload extends Post
{
    protected DateTime $dateTime;
    protected TemporaryFileFactory $temporaryFileFactory;
    protected Filesystem $fileSystem;

    public function __construct(
        JsonFactory $resultJsonFactory,
        SecurityHelper $securityHelper,
        RequestInterface $request,
        LoggerInterface $logger,
        DateTime $dateTime,
        TemporaryFileFactory $temporaryFileFactory,
        Filesystem $fileSystem
    ) {
        parent::__construct($resultJsonFactory, $securityHelper, $request, $logger);

        $this->dateTime = $dateTime;
        $this->temporaryFileFactory = $temporaryFileFactory;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException|NoSuchUploadAdapterInterface
     */
    public function execute(): Json
    {
        if ($this->incorrectSignature()) {
            return $this->throwException(new HttpException(403, 'Incorrect Signature'));
        } elseif ($this->signatureExpired()) {
            return $this->throwException(new HttpException(403, 'Signature Expired'));
        }

        try {
            $result = $this->resultJsonFactory->create();

            return $result->setData([
                'paths' => $this->stash()
            ]);
        } catch (LocalizedException | FileSystemException | Exception $exception) {
            return $this->throwException(new HttpException(422, 'Unprocessable Content'));
        }
    }

    protected function incorrectSignature()
    {
        $signature = $this->securityHelper->generateRouteSignature('magewire/post/upload', [
            'signature' => $this->request->getParam('expires', 0)
        ]);

        return $this->request->getParam('signature') === $signature;
    }

    protected function signatureExpired()
    {
        $timestamp = $this->dateTime->gmtTimestamp();
        return $timestamp > (int) $this->request->getParam('expires', $timestamp);
    }

    /**
     * Stash files temporarily (e.g. var/tmp/ directory).
     *
     * @param array<string, array{
     *     name: string,
     *     type: string,
     *     tmp_name: string,
     *     error: int,
     *     size: int,
     * }> $files
     * @throws FileSystemException
     * @throws Exception
     */
    public function stash()
    {
        $paths = [];
        $files = $this->request->getFiles('files', []);

        foreach (array_keys($files) as $file) {
            $file = $this->temporaryFileFactory->create(['fileId' => 'files[' . $file . ']']);
            $fileDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);

            $name = $file->generateHashNameWithOriginalNameEmbedded();

            $file->setAllowCreateFolders(true);
            $file->setAllowRenameFiles(true);
            $file->setFilenamesCaseSensitivity(false);

            $file->validateFile();

            $file->save($fileDirectory->getAbsolutePath('magewire'), $name);
            $paths[] = $file->getUploadedFileName();
        }

        return $paths;
    }
}
