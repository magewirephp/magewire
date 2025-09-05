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

class Fragment extends ScopeDirective
{
    #[ScopeDirectiveChain(['endfragment'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function fragment(string $type): string
    {
        return "<?php \${$this->var('fragment')} = \$__magewire->utils()->fragment()->make({$type})->start() ?>";
    }

    public function endfragment(): string
    {
        return "<?php \${$this->var('fragment')}->end() ?>";
    }
}
