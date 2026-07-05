<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Magewire;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\ScopeDirective;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\ScopeDirectiveChain;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\ScopeDirectiveParser;

class Slot extends ScopeDirective
{
    #[ScopeDirectiveChain(methods: ['endSlot'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function slot(string $target, string $variable): string
    {
        $var = $this->variableScopeStart($variable);

        return "<?php \${$var} = \$__magewire->factory()->components()->slot('{$target}', \$block) ?>";
    }

    public function endSlot(): string
    {
        $var = $this->variableScopeEnd();

        return "<?php \${$var}->end() ?>";
    }
}
