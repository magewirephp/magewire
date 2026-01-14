<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magento\Framework\Filesystem\DirectoryList;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\FileSystem;

class FileManager
{
    public function __construct(
        private FileSystem $filesystem,
        private DirectoryList $directoryList
    ) {
        //
    }

    public function system(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @todo Enhance path generation to be context-aware:
     *       - Include store view identifiers in the hash to support multi-store setups
     *       - Incorporate theme information to prevent collision between themes
     *       - Consider locale/language variations that may affect the same base template
     *       Currently, identical file paths across different stores/themes will generate
     *       the same compiled file, potentially causing incorrect view rendering.
     */
    public function generateFilePath(string $path, bool $includeResourceDir = true): string
    {
        $resource = $this->getResourcePath();
        $path = sha1($path) . '.phtml';

        return $includeResourceDir ? $resource . DIRECTORY_SEPARATOR . $path : $path;
    }

    protected function getResourcePath(): string
    {
        return $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::GENERATED)
            . DIRECTORY_SEPARATOR
            . 'code'
            . DIRECTORY_SEPARATOR
            . 'Magewirephp'
            . DIRECTORY_SEPARATOR
            . 'Magewire'
            . DIRECTORY_SEPARATOR
            . 'views';
    }
}
