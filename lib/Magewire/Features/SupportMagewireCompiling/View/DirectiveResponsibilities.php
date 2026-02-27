<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

class DirectiveResponsibilities
{
    private array $items = [];

    public function push(string $directive, Directive $predecessor): static
    {
        $this->items[$directive][] = $predecessor;

        return $this;
    }

    public function pop(string $directive): Directive|null
    {
        if (is_array($this->items[$directive] ?? null)) {
            return array_pop($this->items[$directive]);
        }

        return null;
    }

    public function has(string $directive): bool
    {
        return count($this->items[$directive] ?? []) > 0;
    }
}
