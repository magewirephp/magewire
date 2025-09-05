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

class Scope extends ScopeDirective
{
    #[ScopeDirectiveChain(methods: ['elseif', 'else', 'endif'], strict: true)]
    #[ScopeDirectiveParser(ExpressionParserType::CONDITION)]
    public function if(string $condition): string
    {
        return "<?php if ($condition): ?>";
    }

    #[ScopeDirectiveParser(ExpressionParserType::CONDITION)]
    public function elseif(string $condition): string
    {
        return "<?php elseif ($condition): ?>";
    }

    public function else(): string
    {
        return "<?php else: ?>";
    }

    public function endif(): string
    {
        return "<?php endif ?>";
    }

    #[ScopeDirectiveChain(methods: ['endforeach'])]
    #[ScopeDirectiveParser(ExpressionParserType::ITERATION_CLAUSE)]
    public function foreach(string $iterationClause): string
    {
        return "<?php foreach ($iterationClause): ?>";
    }

    public function endforeach(): string
    {
        return "<?php endforeach ?>";
    }
}
