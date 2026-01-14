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
        // Create the initial pipeline.
        $pipeline = parent::newCompilerPipelineDistributorInstance();
        // Elements middleware group for things like <magewire-...
        $pipeline->template()->middleware()->group('elements', 150);

        return $pipeline;
    }
}
