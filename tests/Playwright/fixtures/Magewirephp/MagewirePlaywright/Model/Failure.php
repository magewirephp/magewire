<?php

declare(strict_types=1);

namespace Magewirephp\MagewirePlaywright\Model;

use RuntimeException;
use TypeError;

class Failure
{
    public function raise(string $kind): void
    {
        $message = 'MAGEWIRE_PLAYWRIGHT_PRIVATE_DETAIL /private/magewire-playwright/source.php ' . __FILE__;

        if ($kind === 'exception') {
            throw new RuntimeException($message);
        }

        if ($kind === 'type-error') {
            throw new TypeError($message);
        }
    }
}
