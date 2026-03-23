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
use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Support\Random;

class MagewireFlashMessages
{
    /** @var FlashMessage[] $messages */
    private array $messages = [];

    public function make(
        string|Phrase $message,
        FlashMessageType $type = FlashMessageType::Notice,
        string|null $name = null
    ): FlashMessage
    {
        return $this->messages[$name ?? Random::string()] ??= Factory::create(FlashMessage::class, [
            'message' => is_string($message) ? __($message) : $message,
            'type' => $type
        ]);
    }

    public function unset(string $name): static
    {
        if (isset($this->messages[$name])) {
            unset($this->messages[$name]);
        }

        return $this;
    }

    public function fetch(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * @deprecated clearing all flash messages shouldn't be something you use. Instead, use unset to remove
     *             a single flash message by its name.
     * @see static::unset()
     */
    public function clear(): static
    {
        $this->messages = [];
        return $this;
    }
}
