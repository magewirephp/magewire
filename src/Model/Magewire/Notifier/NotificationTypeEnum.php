<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Magewire\Notifier;

enum NotificationTypeEnum: string
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';

    public function getCssClass(): string
    {
        return 'notification-' . $this->value;
    }
}
