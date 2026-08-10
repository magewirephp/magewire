<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Magewire\Notifier;

use Magewirephp\Magewire\Support\Enum\MessageType;

/**
 * Kept at its original name and location for anything still referencing it.
 *
 * Superseded by MessageType, which says how much a message weighs without tying that to the
 * notifier: a message can just as well surface as a modal or an inline banner.
 *
 * Enums cannot extend one another in PHP, so this cannot be a subclass of its replacement. The
 * cases are declared again and the values kept identical, which makes toMessageType() a total
 * conversion in both directions.
 *
 * @deprecated Use MessageType instead.
 * @see MessageType
 */
enum NotificationTypeEnum: string
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';

    /**
     * Bridge into the replacement, for handing an old value to anything expecting the new type.
     */
    public function toMessageType(): MessageType
    {
        return MessageType::from($this->value);
    }

    /**
     * @deprecated Use MessageType::type() instead.
     */
    public function getType(): string
    {
        return $this->value;
    }

    /**
     * @deprecated Presentation belongs to whatever renders the message, not to its type.
     */
    public function getCssClass(): string
    {
        return 'notification-' . $this->value;
    }
}
