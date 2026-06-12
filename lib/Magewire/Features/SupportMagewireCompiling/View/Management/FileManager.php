<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\View\DesignInterface;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\FileSystem;

class FileManager
{
    public function __construct(
        private readonly FileSystem $filesystem,
        private readonly DirectoryList $directoryList,
        private readonly ApplicationState $appState
    ) {
    }

    public function system(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Get the path to the compiled version of a view.
     */
    public function generateFilePath(string $path, bool $includeResourceDir = true): string
    {
        $resource = $this->getResourcePath();
        $path = trim(str_replace($this->directoryList->getRoot(), '', $path), DIRECTORY_SEPARATOR);

        return $includeResourceDir
            ? $resource . DIRECTORY_SEPARATOR . $path
            : $path;
    }

    protected function getResourcePath(): string
    {
        return (
            $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR
            . 'magewire'
            . DIRECTORY_SEPARATOR
            . 'views'
            . DIRECTORY_SEPARATOR
            . $this->resolveAreaSegment()
        );
    }

    private function resolveAreaSegment(): string
    {
        try {
            return $this->appState->getAreaCode();
        } catch (LocalizedException) {
            return 'global';
        }
    }
}
