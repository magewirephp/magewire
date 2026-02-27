<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler\MagentoTemplateCompiler;
use Magewirephp\Magewire\Support\Concerns\AsFactory;

class CompilerFactory
{
    use AsFactory;

    public function __construct(
        private string $instance = Compiler::class
    ) {
        //
    }

    public function newCompilerInstance(array $arguments = [])
    {
        return $this->newInstance($arguments);
    }

    private function newInstanceType(): string
    {
        return MagentoTemplateCompiler::class;
    }
}
