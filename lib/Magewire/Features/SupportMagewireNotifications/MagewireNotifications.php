<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireNotifications;

use Magento\Framework\Phrase;
use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Support\Random;

class MagewireNotifications
{
    /** @var Notification[] $notifications */
    private array $notifications = [];

    public function make(Phrase $notification, string|null $name = null): Notification
    {
        return $this->notifications[$name ?? Random::string()] ??= Factory::create(Notification::class, [
            'notification' => $notification
        ]);
    }

    public function unset(string $name): static
    {
        if (isset($this->notifications[$name])) {
            unset($this->notifications[$name]);
        }

        return $this;
    }

    public function fetch(): array
    {
        return $this->notifications;
    }

    public function count(): int
    {
        return count($this->notifications);
    }
}
