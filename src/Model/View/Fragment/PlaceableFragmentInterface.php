<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

interface PlaceableFragmentInterface
{
    /**
     * Transfer the rendered script fragment output into a named script placement.
     */
    public function placement(string $placement): static;

    /**
     * Alias for {@see placement()}.
     */
    public function for(string $placement): static;
}
