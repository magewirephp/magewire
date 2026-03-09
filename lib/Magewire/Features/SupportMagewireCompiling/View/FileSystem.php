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
        private readonly File $magentoFileSystemDriver,
        private readonly LoggerInterface $logger
    ) {
        
    }

    /**
     * @throws FileSystemException
     */
    public function read(string $from): string
    {
        return $this->magentoFileSystemDriver->fileGetContents($from);
    }

    /**
     * @throws FileSystemException
     */
    public function write(string $content, string $to, int|string $mode = 0): void
    {
        $this->magentoFileSystemDriver->filePutContents($to, $content, $mode);
    }

    public function exists(string $path): bool
    {
        try {
            return $this->magentoFileSystemDriver->isExists($path);
        } catch (FileSystemException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        return false;
    }

    /**
     * @throws FileSystemException
     */
    public function stats(string $path): array
    {
        return $this->magentoFileSystemDriver->stat($path);
    }

    /**
     * @throws FileSystemException
     */
    public function makeDirectory(string $path, int $mode = 0o755, bool $recursive = false): void
    {
        $this->magentoFileSystemDriver->createDirectory($path, $mode);
    }

    /**
     * @throws FileSystemException
     */
    public function lastModified(string $path): int
    {
        $stat = $this->stats($path);

        return $stat['mtime'] ?? time();
    }

    /**
     * @throws FileSystemException
     */
    public function ensureDirectoryExists(string $path): void
    {
        if ($this->exists(dirname($path))) {
            return;
        }

        $this->makeDirectory(dirname($path), 0o777, true);
    }
}
