<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload\Adapter;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\Upload\AbstractAdapter;
use Magewirephp\Magewire\Model\Upload\File\TemporaryUploader;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;

class Local extends AbstractAdapter
{
    public const NAME = 'local';

    protected Filesystem $fileSystem;
    protected Random $randomizer;

    public function __construct(
        DateTime $dateTime,
        SecurityHelper $securityHelper,
        FileDriver $fileDriver,
        RequestInterface $request,
        Filesystem $fileSystem,
        Random $randomizer
    ) {
        parent::__construct($dateTime, $securityHelper, $fileDriver, $request);

        $this->fileSystem = $fileSystem;
        $this->randomizer = $randomizer;
    }

    /**
     * @param array<TemporaryUploader> $files
     */
    public function stash(array $files): array
    {
        $paths = [];

        foreach ($files as $file) {
            $fileDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
            $name = $file->generateHashNameWithOriginalNameEmbedded();

            $file->save($fileDirectory->getAbsolutePath('magewire'), $name);
            $paths[] = $file->getUploadedFileName();
        }

        return $paths;
    }

    public function store(array $paths, string $directory = null): array
    {
        $fileDirectoryTmp = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $fileDirectoryUpload = $this->fileSystem->getDirectoryWrite(DirectoryList::UPLOAD);

        return array_map(function ($tmp) use ($fileDirectoryTmp, $fileDirectoryUpload) {
            $path = 'magewire' . DIRECTORY_SEPARATOR . $tmp;

            if ($fileDirectoryTmp->isFile($path)) {
                if ($fileDirectoryTmp->copyFile($path, $path, $fileDirectoryUpload)) {
                    return $fileDirectoryUpload->getRelativePath($path);
                }
            }

            return null;
        }, $paths);
    }
}
