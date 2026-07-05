<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\Contracts\ViewCompilerInterface;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\CompilerPipelines;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\CompilerPipelinesFactory;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management\CompilerManager;

class MagentoTemplateCompiler extends Compiler
{
    /**
     * @param array<string|int, ViewCompilerInterface> $middleware
     */
    public function __construct(
        CompilerManager $manager,
        CompilerPipelinesFactory $compilerPipelinesFactory,
        private readonly array $middleware = []
    ) {
        parent::__construct($manager, $compilerPipelinesFactory);
    }

    protected function newCompilerPipelineDistributorInstance(): CompilerPipelines
    {
        $pipeline = parent::newCompilerPipelineDistributorInstance();

        foreach ($this->middleware as $group => $middleware) {
            $pipeline
                ->template()
                ->middleware()
                ->group($group)
                ->pipe(static fn (string $throughput, callable $next): mixed => $next($middleware->compile($throughput)));
        }

        return $pipeline;
    }
}
