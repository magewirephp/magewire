<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload\Adapter;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;

class Local implements UploadAdapterInterface
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
            'expires' => $this->dateTime->gmtTimestamp() + 1900
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

    public function getRoute(): string
    {
        return 'magewire/post/upload_local';
    }

    /**
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function hasCorrectSignature(): bool
    {
        $signature = $this->securityHelper->generateRouteSignature($this->getRoute(), [
            'expires' => $this->request->getUserParam('expires', 0)
        ]);

        return $this->request->getUserParam('signature') === $signature;
    }

    public function signatureHasNotExpired(): bool
    {
        $timestamp = $this->dateTime->gmtTimestamp();
        return $timestamp > (int) $this->request->getUserParam('expires', $timestamp);
    }
}
