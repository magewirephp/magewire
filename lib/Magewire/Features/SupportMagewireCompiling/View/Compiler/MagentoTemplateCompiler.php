<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\CompilerPipelines;

class MagentoTemplateCompiler extends Compiler
{
    protected function newCompilerPipelineDistributorInstance(): CompilerPipelines
    {
        $distributor = parent::newCompilerPipelineDistributorInstance();

        // Middleware group that sits right after the cores 'first'.
        $distributor->template()->middleware()->group('components', 101);
        // Middleware group that sits right after the cores 'last'.
        $distributor->template()->middleware()->group('shutdown', 901);

        return $distributor;
    }
}
