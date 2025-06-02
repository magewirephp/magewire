<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext;

use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerTypeContext;

class HandlerTypeListeners extends HandlerTypeContext
{
    public function for(string $event, array $options = []): self
    {
        $this->handler()->data()->push('listeners', fn () => ['options' => $options], $event);

        return $this;
    }
}
