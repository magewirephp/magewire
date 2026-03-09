<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirective;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveChain;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveParser;

class Template extends ScopeDirective
{
    #[ScopeDirectiveChain(methods: ['endtemplate'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function template(): string
    {
        $var = $this->variableScopeStart();

        return "<?php \${$var} = \$__magewire->utils()->fragment()->make()->component(\$block) ?>";
    }

    public function endtemplate(): string
    {
        $var = $this->variableScopeEnd();

        return "<?php \${$var}->end() ?>";
    }
}
