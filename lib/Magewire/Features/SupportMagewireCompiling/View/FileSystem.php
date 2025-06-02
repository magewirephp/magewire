<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class FileSystem
{
    public function __construct(
        private readonly File $magentoFilesystemDriver,
        private readonly LoggerInterface $logger
    ) {
        //
    }

    public function exists(string $path): bool
    {
        try {
            return $this->magentoFilesystemDriver->isExists($path);
        } catch (FileSystemException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        return false;
    }

    /**
     * @throws FileSystemException
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): void
    {
        $this->magentoFilesystemDriver->createDirectory($path, $mode);
    }

    public function lastModified(string $path): int
    {
        $stat = $this->magentoFilesystemDriver->stat($path);

        return $stat['mtime'];
    }

    /**
     * @throws FileSystemException
     */
    public function get(string $path): string
    {
        return $this->magentoFilesystemDriver->fileGetContents($path);
    }

    /**
     * @throws FileSystemException
     */
    public function put(string $path, string $contents): void
    {
        $this->magentoFilesystemDriver->filePutContents($path, $contents);
    }

    public function ensureDirectoryExists(string $path): void
    {
        if ($this->exists(dirname($path))) {
            return;
        }

        $this->makeDirectory(dirname($path), 0777, true);
    }
}
