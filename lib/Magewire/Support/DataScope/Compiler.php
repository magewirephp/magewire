<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Support\DataScope;

use Magewirephp\Magewire\Support\DataScope;

abstract class Compiler
{
    /** @var array<string, object> $uses */
    protected array $uses = [];

    abstract public function compile(DataScope $data): array|string;

    /**
     * Assign an additional dependency to be used by callable values requiring
     * access to global variables that are otherwise inaccessible during injection.
     */
    public function use(string $name, object $object): static
    {
        $this->uses[$name] = $object;

        return $this;
    }

    protected function uses(): array
    {
        return $this->uses;
    }
}
