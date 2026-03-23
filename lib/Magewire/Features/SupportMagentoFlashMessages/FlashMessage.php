<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoFlashMessages;

use Magento\Framework\Phrase;

class FlashMessage
{
    private FlashMessageType $type = FlashMessageType::Notice;

    function __construct(
        private Phrase $message
    ) {
    }

    public function withMessage(string|Phrase $message): static
    {
        if (is_string($message)) {
            $message = __($message);
        }

        $this->message = $message;
        return $this;
    }

    public function asSuccess(): static
    {
        return $this->as(FlashMessageType::Success);
    }

    public function asError(): static
    {
        return $this->as(FlashMessageType::Error);
    }

    public function asWarning(): static
    {
        return $this->as(FlashMessageType::Warning);
    }

    public function asNotice(): static
    {
        return $this->as(FlashMessageType::Notice);
    }

    public function as(FlashMessageType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function message(): Phrase
    {
        return $this->message;
    }

    public function type(): FlashMessageType
    {
        return $this->type;
    }

    /**
     * @deprecated Use the message() method instead.
     * @see static::message()
     */
    function getMessage(): Phrase
    {
        return $this->message;
    }

    /**
     * @deprecated Use the type() method instead.
     * @see static::type()
     */
    function getType(): FlashMessageType
    {
        return $this->type;
    }
}
