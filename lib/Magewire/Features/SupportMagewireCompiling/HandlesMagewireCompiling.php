<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;

trait HandlesMagewireCompiling
{
    private Compiler|null $compiler = null;
    private bool $compile = true;

    public function compiler(Compiler $compiler = null): Compiler|null
    {
        if ($compiler) {
            $this->compiler = $compiler;
        }

        return $this->compiler;
    }
}
