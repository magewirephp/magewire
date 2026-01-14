<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magewire;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirective;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveChain;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveParser;

class Component extends ScopeDirective
{
    #[ScopeDirectiveChain(methods: ['endComponent'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function component(string $type, string $id, string|null $variant = null): string
    {
        $var = $this->variableScopeStart($id);

        return "<?php \${$var} = \$__magewire->utils()->fragment()->make()->element('{$type}', '{$variant}', \$block)->start() ?>";
    }

    public function endComponent(): string
    {
        $var = $this->variableScopeEnd();

        return "<?php \${$var}->end() ?>";
    }
}
