<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext;

use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\Listeners\Event;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerTypeContext;

class HandlerTypeInteractions extends HandlerTypeContext
{
    public function onClick(): static
    {
        $this->handler()->listen()->for('click');

        return $this;
    }

    public function onDoubleClick(): static
    {
        $this->handler()->listen()->for('dblclick');

        return $this;
    }

    public function onMouseOver(): static
    {
        $this->handler()->listen()->for('mouseover');

        return $this;
    }

    public function onMouseOut(): static
    {
        $this->handler()->listen()->for('mouseout');

        return $this;
    }
}
