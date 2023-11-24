<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Storage\Driver;

use Magewirephp\Magewire\Model\Storage\StorageDriver;

class S3 extends StorageDriver
{
    public function store(array $paths, string $directory = null): array
    {
        // TODO: Implement store() method.
    }

    public function publish(array $paths, string $directory = null, string $filename = null): array
    {
        // TODO: Implement publish() method.
    }
}
