<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

class Slot
{
    private string|null $content = null;

    public function __construct(
        private readonly string $name
    ) {
        //
    }

    public function name(): string
    {
        return $this->name;
    }

    public function update(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function __toString(): string
    {
        return $this->content ??= sprintf('<!-- __MAGEWIRE_SLOT_%s__ -->', $this->name);
    }
}
