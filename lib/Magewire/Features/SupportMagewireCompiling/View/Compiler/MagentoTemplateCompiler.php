<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\Contracts\ViewCompilerInterface;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\CompilerPipelines;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\CompilerPipelinesFactory;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\CompilerManager;

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
                ->pipe(fn (string $throughput, callable $next): mixed => $next($middleware->compile($throughput)));
        }

        return $pipeline;
    }
}
