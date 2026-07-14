<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Exceptions;

use Exception;
use Magento\Framework\Phrase;
use Throwable;

class SilentException extends Exception
{
    public function __construct(
        string|Phrase $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct((string) $message, $code, $previous);
    }
}
