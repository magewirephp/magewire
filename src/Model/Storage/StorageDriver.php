<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Storage;

use Magewirephp\Magewire\Model\Storage;

abstract class StorageDriver
{
    /**
     * Publish the temporary file to its designated public destination
     * and provide an array containing the relative file paths..
     *
     * @return  array<int, string>
     */
    abstract public function publish(array $paths, string $directory = null, string $filename = null): array;
}
