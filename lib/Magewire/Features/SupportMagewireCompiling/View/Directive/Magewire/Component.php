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
use Magewirephp\Magewire\Support\Php;

class Component extends ScopeDirective
{
    #[ScopeDirectiveChain(methods: ['endComponent'])]
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function component(string $prefix, string $id, string $variable, string|null $type = null): string
    {
        $var = $this->variableScopeStart($variable);
        $prefix ??= 'default';
        $prefix = Php::stringLiteral($prefix);
        $type = Php::stringLiteral((string) $type);
        $id = Php::stringLiteral($id);

        return "<?php \${$var} = \$__magewire->factory()->components()->component(prefix: {$prefix}, block: \$block, type: {$type}, id: {$id})->track() ?>";
    }

    public function endComponent(): string
    {
        $var = $this->variableScopeEnd();

        return "<?php \${$var}->end()->untrack() ?>";
    }
}
