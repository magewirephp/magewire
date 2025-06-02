<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

abstract class ScopeDirective extends Directive
{
    abstract public function start(string $expression, string $directive): string;
    abstract public function end(string $directive): string;

    public function compile(string $expression, string $directive): string
    {
        return str_starts_with($directive, 'end') ? $this->end($directive) : $this->start($expression, $directive);
    }
}
