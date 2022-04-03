<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload\Adapter;

use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Local implements UploadAdapterInterface
{
    protected DateTime $time;
    protected Filesystem $fileSystem;
    protected UploaderFactory $uploaderFactory;
    protected WriteInterface $varDirectory;
    protected SecurityHelper $securityHelper;
    protected FileFactory $fileFactory;

    /**
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param DateTime $time
     * @param SecurityHelper $securityHelper
     * @param FileFactory $fileFactory
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        DateTime $time,
        SecurityHelper $securityHelper,
        FileFactory $fileFactory
    ) {
        $this->fileSystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->time = $time;
        $this->securityHelper = $securityHelper;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @param array $file
     * @param bool $isMultiple
     * @return string
     * @throws FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function generateSignedUploadUrl(array $file, bool $isMultiple): string
    {
        return $this->securityHelper->generateRouteSignature('magewire/post/upload_local', [
            'expires' => $this->time->gmtTimestamp() + 1900
        ]);
    }

    public function getGenerateSignedUploadUrlEvent(): string
    {
        return 'upload:generatedSignedUrl';
    }
}
