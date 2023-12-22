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
    public function publish(array $paths, string $directory = null, string $filename = null): array
    {
        // WIP
    }
}
