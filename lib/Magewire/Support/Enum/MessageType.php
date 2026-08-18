<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\Enum;

/**
 * How much a message meant for the customer weighs.
 *
 * Deliberately says nothing about presentation. Whatever renders a message decides what its type
 * looks like, so the same value serves a notifier, a modal, an inline banner or a response header.
 *
 * @api
 */
enum MessageType: string
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';

    public function type(): string
    {
        return $this->value;
    }
}
