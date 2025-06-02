<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Instructions;

use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\ViewInstruction;

class Action extends ViewInstruction
{
    private string|null $action = null;

    public function getType(): string
    {
        return 'action';
    }

    public function getAction(): string
    {
        return 'action';
    }

    public function execute(string $action): static
    {
        $this->action = $action;

        return $this;
    }
}
