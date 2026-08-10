<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\Exceptions;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Support\Enum\MessageType;

/**
 * Every method here increments the same counter, which is the whole point.
 *
 * A request filter rejects before reconstruction, so a rejected call must leave the counter
 * untouched. Were the rejection to happen anywhere later, the counter would move and give it away.
 */
class Basic extends Component
{
    public int $count = 0;

    /**
     * Control: nothing rejects this one, so it must commit.
     */
    public function increment(): void
    {
        $this->count++;
    }

    public function rejectWarning(): void
    {
        $this->count++;
    }

    public function rejectError(): void
    {
        $this->count++;
    }

    public function rejectInfo(): void
    {
        $this->count++;
    }

    public function rejectSuccess(): void
    {
        $this->count++;
    }

    /**
     * Rejects from inside the component, after reconstruction, rather than from a filter. Same
     * exception base, so the same handler answers it and the frontend cannot tell the difference.
     */
    public function rejectFromComponent(): void
    {
        $this->count++;

        throw new PlaywrightRejectionException(
            418,
            MessageType::ERROR,
            'Rejected from within the component.'
        );
    }
}
