<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

/**
 * @deprecated Use DispatchBrowserEvent for the legacy dispatchBrowserEvent() API.
 * @see DispatchBrowserEvent
 */
trait BrowserEvent
{
    use DispatchBrowserEvent;
}
