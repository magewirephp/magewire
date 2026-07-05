<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler;

trait HandlesMagewireCompiling
{
    private Compiler|null $magewireCompiler = null;

    public function magewireCompiler(Compiler|null $compiler = null): Compiler|null
    {
        if ($compiler) {
            $this->magewireCompiler = $compiler;
        }

        return $this->magewireCompiler;
    }
}
