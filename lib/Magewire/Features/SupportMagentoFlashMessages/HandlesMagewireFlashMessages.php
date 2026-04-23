<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoFlashMessages;

use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Features\SupportMagentoFlashMessages\FlashMessage as FlashMessageElement;

trait HandlesMagewireFlashMessages
{
    private MagewireFlashMessages|null $magewireFlashMessages = null;

    public function magewireFlashMessages(): MagewireFlashMessages
    {
        return $this->magewireFlashMessages ??= Factory::create(MagewireFlashMessages::class);
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function dispatchErrorMessage($message): FlashMessageElement
    {
        return $this->magewireFlashMessages()->make($message, FlashMessageType::Error);
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function dispatchWarningMessage($message): FlashMessageElement
    {
        return $this->magewireFlashMessages()->make($message, FlashMessageType::Warning);
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function dispatchNoticeMessage($message): FlashMessageElement
    {
        return $this->magewireFlashMessages()->make($message, FlashMessageType::Notice);
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function dispatchSuccessMessage($message): FlashMessageElement
    {
        return $this->magewireFlashMessages()->make($message, FlashMessageType::Success);
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function dispatchMessage(string $type, $message): FlashMessageElement
    {
        $type = match ($type) {
            FlashMessageType::Error->value   => FlashMessageType::Error,
            FlashMessageType::Warning->value => FlashMessageType::Warning,
            FlashMessageType::Success->value => FlashMessageType::Success,

            default => FlashMessageType::Notice
        };

        return $this->magewireFlashMessages()->make($message, $type);
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function hasFlashMessages(): bool
    {
        return $this->magewireFlashMessages()->count() > 0;
    }

    /**
     * @deprecated Flash Messages have been moved into their own object. Chain the magewireFlashMessages method instead.
     * @see static::magewireFlashMessages()
     */
    public function getFlashMessages(): array
    {
        return $this->magewireFlashMessages()->fetch();
    }

    /**
     * @deprecated Clearing all messages at once is not advisable. Use the unset method instead.
     * @see MagewireFlashMessages::unset()
     */
    public function clearFlashMessages(): void
    {
        $this->magewireFlashMessages()->clear();
    }
}
