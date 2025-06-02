<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Instructions\Magento;

use Magento\Framework\Phrase;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\ViewInstruction;

class FlashMessage extends ViewInstruction
{
    public function getType(): string
    {
        return 'flash-message';
    }

    public function getAction(): string
    {
        return 'mage-flash-message';
    }

    public function withMessage(?Phrase $message): static
    {
        $this->data()->set('args.text', $message->render());
        return $this;
    }
}
