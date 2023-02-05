<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Hyva\CheckoutCore\Model\Magewire\UpdateAdapterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\MediaStorage\Model\File\UploaderFactory as FileUploaderFactory;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;

abstract class AbstractAdapter implements UploadAdapterInterface
{
    protected DateTime $dateTime;
    protected SecurityHelper $securityHelper;
    protected FileDriver $fileDriver;
    protected RequestInterface $request;

    public function __construct(
        DateTime $dateTime,
        SecurityHelper $securityHelper,
        FileDriver $fileDriver,
        RequestInterface $request
    ) {
        $this->dateTime = $dateTime;
        $this->securityHelper = $securityHelper;
        $this->fileDriver = $fileDriver;
        $this->request = $request;
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function generateSignedUploadUrl(array $file, bool $isMultiple): string
    {
        return $this->securityHelper->generateRouteSignatureUrl($this->getRoute(), [
            UploadAdapterInterface::QUERY_PARAM_EXPIRES => $this->dateTime->gmtTimestamp() + 1900,
            UploadAdapterInterface::QUERY_PARAM_ADAPTER => $this->getName()
        ]);
    }

    public function getGenerateSignedUploadUrlEvent(): string
    {
        return 'upload:generatedSignedUrl';
    }

    public function getDriver(): Filesystem\DriverInterface
    {
        return $this->fileDriver;
    }

    public function getName(): string
    {
        return $this::NAME;
    }

    public function getRoute(): string
    {
        return 'magewire/post/upload';
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function hasCorrectSignature(): bool
    {
        $signature = $this->securityHelper->generateRouteSignature($this->getRoute(), [
            UploadAdapterInterface::QUERY_PARAM_EXPIRES => $this->request->getUserParam(UploadAdapterInterface::QUERY_PARAM_EXPIRES, 0),
            UploadAdapterInterface::QUERY_PARAM_ADAPTER => $this->getName()
        ]);

        return $this->request->getUserParam(UploadAdapterInterface::QUERY_PARAM_SIGNATURE) === $signature;
    }

    public function signatureHasNotExpired(): bool
    {
        $timestamp = $this->dateTime->gmtTimestamp();
        return $timestamp > (int) $this->request->getUserParam(UploadAdapterInterface::QUERY_PARAM_EXPIRES, $timestamp);
    }
}
