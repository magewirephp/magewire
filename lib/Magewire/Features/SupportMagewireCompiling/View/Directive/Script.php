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
use Magewirephp\Magewire\Support\Php;
use UnexpectedValueException;

class Script extends ScopeDirective
{
    #[ScopeDirectiveChain(['endscript'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function script(mixed $placement = null): string
    {
        $script = "\$__magewire->utils()->fragment()->make()->script()";

        if ($placement !== null) {
            $script .= sprintf('->placement(%s)', $this->placementExpression($placement));
        }

        return "<?php \${$this->var('fragment')} = {$script}->start() ?>";
    }

    public function endscript(): string
    {
        return "<?php \${$this->var('fragment')}->end() ?>";
    }

    private function placementExpression(mixed $placement): string
    {
        if (is_array($placement) && ( $placement['type'] ?? null ) === 'variable') {
            return Php::variable($placement['name']);
        }

        if (is_string($placement)) {
            return Php::stringLiteral($placement);
        }

        throw new UnexpectedValueException('The @script(placement: ...) argument must be a string or variable expression.');
    }
}
