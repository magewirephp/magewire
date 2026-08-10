<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Exceptions;

use Magewirephp\Magewire\Exceptions\RequestFilterException;
use Magewirephp\Magewire\Support\Enum\MessageType;

/**
 * Carries whichever status and severity a Playwright case asks for, so one exception can stand in
 * for the whole range a real filter might produce.
 */
class PlaywrightRejectionException extends RequestFilterException
{
    public function __construct(
        private readonly int $status,
        private readonly MessageType $severity,
        string $message
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function severity(): MessageType
    {
        return $this->severity;
    }
}
