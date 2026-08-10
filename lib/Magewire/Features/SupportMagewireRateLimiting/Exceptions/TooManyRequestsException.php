<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions;

use Magewirephp\Magewire\Exceptions\RequestFilterException;
use Throwable;

class TooManyRequestsException extends RequestFilterException
{
    public function __construct(string $message = '', int $code = 0, Throwable|null $previous = null)
    {
        parent::__construct($message === '' ? (string) __('Too many requests! Please wait.') : $message, $code, $previous);
    }

    public function status(): int
    {
        return 429;
    }
}
