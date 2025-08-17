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
    // IMPORTANT: sort order does matter to determine the state level.
    case IDLE = 'idle';
    case RUNNING = 'running';
    case HOLD = 'hold';
    case FAILED = 'failed';
    case SUCCEEDED = 'succeeded';
    case TERMINATED = 'terminated';
    case RECOVERED = 'recovered';

    public function getState(): string
    {
        return $this->value;
    }

    public function getCssClass(): string
    {
        return 'state-' . $this->value;
    }

    public function getLevel(): int
    {
        $level = array_search($this, self::cases(), true);
        return $level !== false ? $level : 0;
    }
}
