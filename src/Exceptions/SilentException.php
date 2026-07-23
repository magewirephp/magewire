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
    /**
     * Magento 2.4.6 cannot reflect union-typed constructor parameters.
     *
     * @param string|Phrase $message
     */
    public function __construct(
        mixed $message = '',
        int $code = 0,
        Throwable|null $previous = null
    ) {
        parent::__construct((string) $message, $code, $previous);
    }
}
