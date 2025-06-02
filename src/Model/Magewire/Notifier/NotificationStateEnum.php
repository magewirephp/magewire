<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Magewire\Notifier;

enum NotificationStateEnum: string
{
    case IDLE = 'idle';
    case RUNNING = 'running';
    case FAILED = 'failed';
    case SUCCEEDED = 'succeeded';
    case TERMINATED = 'terminated';

    public function getCssClass(): string
    {
        return 'state-' . $this->value;
    }
}
