<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Storage\Driver;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magewirephp\Magewire\Model\Storage\StorageDriverInterface;

class Local implements StorageDriverInterface
{
    protected Filesystem $fileSystem;

    public function __construct(
        Filesystem $fileSystem
    ) {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @throws FileSystemException
     */
    public function store(array $paths, string $directory = null): array
    {
        $sourceDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $targetDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::UPLOAD);

        return array_map(function($tmp) use ($sourceDirectory, $targetDirectory) {
            $path = 'magewire' . DIRECTORY_SEPARATOR . $tmp;

            if ($sourceDirectory->isFile($path) && $sourceDirectory->copyFile($path, $path, $targetDirectory)) {
                return $targetDirectory->getRelativePath($path);
            }

            return null;
        }, $paths);
    }
}
