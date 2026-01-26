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
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\CompilerPipelinesFactory;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\CompilerManager;

class MagentoTemplateCompiler extends Compiler
{
    public function __construct(
        private Compiler\Middleware\Blade $bladeMiddleware,
        CompilerManager $manager,
        CompilerPipelinesFactory $compilerPipelinesFactory
    ) {
        parent::__construct($manager, $compilerPipelinesFactory);
    }

    protected function newCompilerPipelineDistributorInstance(): CompilerPipelines
    {
        // Create the initial pipeline.
        $pipeline = parent::newCompilerPipelineDistributorInstance();

        // Blade-like template precompiler middleware.
        $pipeline->template()->middleware()->pipe(function (string $throughput, callable $next) {
            return $next($this->bladeMiddleware->compile($throughput));
        });

        return $pipeline;
    }
}
