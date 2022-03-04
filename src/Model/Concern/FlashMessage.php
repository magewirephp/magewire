<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\Phrase;
use Magewirephp\Magewire\Model\Element\FlashMessage as FlashMessageElement;

trait FlashMessage
{
    /** @var FlashMessageElement[] $flashMessage */
    protected array $flashMessage = [];

    /**
     * @param Phrase|string $message
     * @return FlashMessageElement
     */
    public function dispatchErrorMessage($message): FlashMessageElement
    {
        return $this->dispatchMessage(FlashMessageElement::ERROR, $message);
    }

    /**
     * @param Phrase|string $message
     * @return FlashMessageElement
     */
    public function dispatchWarningMessage($message): FlashMessageElement
    {
        return $this->dispatchMessage(FlashMessageElement::WARNING, $message);
    }

    /**
     * @param Phrase|string $message
     * @return FlashMessageElement
     */
    public function dispatchNoticeMessage($message): FlashMessageElement
    {
        return $this->dispatchMessage(FlashMessageElement::NOTICE, $message);
    }

    /**
     * @param Phrase|string $message
     * @return FlashMessageElement
     */
    public function dispatchSuccessMessage($message): FlashMessageElement
    {
        return $this->dispatchMessage(FlashMessageElement::SUCCESS, $message);
    }

    /**
     * @param string $type
     * @param Phrase|string $message
     * @return FlashMessageElement
     */
    public function dispatchMessage(string $type, $message): FlashMessageElement
    {
        return $this->flashMessage[] = new FlashMessageElement($message, $type);
    }

    /**
     * Check if any flash messages have been dispatched.
     *
     * @return bool
     */
    public function hasFlashMessages(): bool
    {
        return count($this->flashMessage) !== 0;
    }

    /**
     * Get all flash messages.
     *
     * @return FlashMessageElement[]
     */
    public function getFlashMessages(): array
    {
        return $this->flashMessage;
    }

    /**
     * Reset all flash messages.
     *
     * @return void
     */
    public function clearFlashMessages(): void
    {
        $this->flashMessage = [];
    }
}
