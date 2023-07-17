<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Storage;

use Magento\Framework\Exception\FileSystemException;
use Magewirephp\Magewire\Model\Upload\TemporaryFile;

interface StorageDriverInterface
{
    /**
     * @throws FileSystemException
     */
    public function store(array $paths, string $directory = null): array;
}
