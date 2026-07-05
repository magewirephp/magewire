<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View;

abstract class FunctionDirective extends Directive
{
    public function compile(string $expression, string $directive): string
    {
        return method_exists($this, $directive) ? $this->$directive($expression) : '';
    }
}
