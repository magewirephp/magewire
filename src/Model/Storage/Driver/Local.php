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
use Magewirephp\Magewire\Model\Storage\StorageDriver;

class Local extends StorageDriver
{
    public const DEFAULT_DIRECTORY = 'magewire';

    protected Filesystem $fileSystem;

    public function __construct(
        Filesystem $fileSystem
    ) {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @throws FileSystemException
     */
    public function publish(array $paths, string $directory = null, string $filename = null): array
    {
        $sourceDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $targetDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::UPLOAD);

        return array_map(function($file) use ($sourceDirectory, $targetDirectory, $directory, $filename) {
            $path = 'magewire' . DIRECTORY_SEPARATOR . $file;
            $info = pathinfo($sourceDirectory->getAbsolutePath() . $path);

            if (! $sourceDirectory->isFile($path)) {
                return null;
            }

            $parts = explode('-', $info['filename']);
            $filename = ($filename ?? $parts[0]) . '.' . $info['extension'];
            $directory ??= self::DEFAULT_DIRECTORY;

            // Making sure the given directory always exists.
            $targetDirectory->create($directory);
            // Detirmine the files final destination.
            $destination = $directory . DIRECTORY_SEPARATOR . $filename;

            if ($sourceDirectory->copyFile($path, $destination, $targetDirectory)) {
                return $targetDirectory->getRelativePath($destination);
            }

            return null;
        }, $paths);
    }
}
