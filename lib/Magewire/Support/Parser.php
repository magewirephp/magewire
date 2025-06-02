<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support;

use Magewirephp\Magewire\Support\Concerns\WithFactory;

abstract class Parser
{
    use WithFactory;

    /**
     * Tries to parse the given content.
     */
    abstract public function parse(string $content): self;
}
