<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Instructions;

use Magento\Framework\Phrase;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\ViewInstruction;

class Notification extends ViewInstruction
{
    public function getType(): string
    {
        return 'notification';
    }

    public function getAction(): string
    {
        return 'notification';
    }

    public function withMessage(?Phrase $message): static
    {
        $this->data()->set('args.text', $message->render());
        return $this;
    }

    public function withDelay(int|null $delay): static
    {
        $this->data()->set('args.delay', $delay);
        return $this;
    }

    public function withTitle(Phrase|null $title): static
    {
        $this->data()->set('args.title', $title);
        return $this;
    }

    public function withType(string|null $type): static
    {
        $this->data()->set('args.type', $type);
        return $this;
    }
}
