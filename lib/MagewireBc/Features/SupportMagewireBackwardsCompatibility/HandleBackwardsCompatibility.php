<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class HandleBackwardsCompatibility
{
    public function __construct(
        public readonly bool $enabled = true
    ) {
    }

    public function isBackwardsCompatible(): bool
    {
        return $this->enabled;
    }
}
