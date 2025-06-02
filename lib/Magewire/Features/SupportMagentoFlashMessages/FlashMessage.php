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
    function __construct(
        private readonly string $message,
        private readonly FlashMessageType $type
    ) {
        //
    }

    function getMessage(): Phrase
    {
        return __($this->message);
    }

    function getType(): FlashMessageType
    {
        return $this->type;
    }
}
