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

class Component extends ScopeDirective
{
    #[ScopeDirectiveChain(methods: ['endComponent'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function component(string $prefix, string $id, string $variable, string|null $type = null): string
    {
        $var = $this->variableScopeStart($variable);
        $prefix ??= 'default';

        return "<?php \${$var} = \$__magewire->factory()->components()->component(prefix: '{$prefix}', block: \$block, type: '{$type}', id: '{$id}')->track() ?>";
    }

    public function endComponent(): string
    {
        $var = $this->variableScopeEnd();

        return "<?php \${$var}->end()->untrack() ?>";
    }
}
