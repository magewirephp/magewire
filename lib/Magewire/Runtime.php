<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magewirephp\Magewire\Enums\RequestMode;
use Magewirephp\Magewire\Enums\RuntimeState;
use RuntimeException;

class Runtime
{
    private RequestMode $mode = RequestMode::UNDEFINED;
    private RuntimeState $state = RuntimeState::UNINITIALIZED;

    public function state(RuntimeState|null $state = null): RuntimeState
    {
        if ($state) {
            $this->state = $state;
        }

        return $this->state;
    }

    /**
     * @throws RuntimeException
     */
    public function mode(RequestMode|null $mode = null): RequestMode
    {
        if ($mode === null || $mode === $this->mode) {
            return $this->mode;
        }
        if ($mode === RequestMode::UNDEFINED) {
            throw new RuntimeException('Magewire request mode cannot be unset.');
        }

        return $this->mode = $mode;
    }
}
