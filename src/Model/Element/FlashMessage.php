<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Element;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Phrase;

class FlashMessage
{
    public const ERROR   = MessageInterface::TYPE_ERROR;
    public const WARNING = MessageInterface::TYPE_WARNING;
    public const NOTICE  = MessageInterface::TYPE_NOTICE;
    public const SUCCESS = MessageInterface::TYPE_SUCCESS;

    /** @var Phrase|string $flashMessage */
    protected $flashMessage;
    protected string $type;

    /**
     * Message constructor.
     * @param Phrase|string $message
     * @param string $type
     */
    public function __construct($message, string $type)
    {
        // Lets for now just assume the developer gives a Phrase or string message.
        $this->flashMessage = is_string($message) ? __($message) : $message;
        $this->type = $type;
    }

    /**
     * @return Phrase
     */
    public function getMessage(): Phrase
    {
        return $this->flashMessage;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
