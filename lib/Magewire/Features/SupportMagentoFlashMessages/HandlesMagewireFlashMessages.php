<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoFlashMessages;

trait HandlesMagewireFlashMessages
{
    /** @var FlashMessage[] $flashMessage */
    private array $flashMessage = [];

    function dispatchErrorMessage($message): FlashMessage
    {
        return $this->dispatchMessage(FlashMessageType::Error, $message);
    }

    function dispatchWarningMessage($message): FlashMessage
    {
        return $this->dispatchMessage(FlashMessageType::Warning, $message);
    }

    function dispatchNoticeMessage($message): FlashMessage
    {
        return $this->dispatchMessage(FlashMessageType::Notice, $message);
    }

    function dispatchSuccessMessage($message): FlashMessage
    {
        return $this->dispatchMessage(FlashMessageType::Success, $message);
    }

    function dispatchMessage(FlashMessageType $type, $message): FlashMessage
    {
        return $this->flashMessage[] = new FlashMessage($message, $type);
    }

    function hasFlashMessages(): bool
    {
        return count($this->flashMessage) !== 0;
    }

    function getFlashMessages(): array
    {
        return $this->flashMessage;
    }

    function clearFlashMessages(): void
    {
        $this->flashMessage = [];
    }
}
