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

class Notification
{
    protected NotificationType $type = NotificationType::Notice;

    protected string|Phrase|null $title = null;
    protected int $duration = 3000;

    public function __construct(
        protected string|Phrase $notification
    ) {
    }

    public function asSuccess(): static
    {
        return $this->as(NotificationType::Success);
    }

    public function asError(): static
    {
        return $this->as(NotificationType::Error);
    }

    public function asWarning(): static
    {
        return $this->as(NotificationType::Warning);
    }

    public function asNotice(): static
    {
        return $this->as(NotificationType::Notice);
    }

    public function as(NotificationType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function withMessage(string|Phrase $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function withTitle(string|Phrase $title): static
    {
        if (is_string($title)) {
            $title = __($title);
        }

        $this->title = $title;
        return $this;
    }

    public function withoutTitle(): static
    {
        $this->title = null;
        return $this;
    }

    public function withDuration(int $milliseconds): static
    {
        $this->duration = $milliseconds;
        return $this;
    }

    public function notification(): Phrase
    {
        return $this->notification;
    }

    public function type(): NotificationType
    {
        return $this->type;
    }
}
