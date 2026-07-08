<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\ScopeDirectiveParser;

class Base extends Directive
{
    #[ScopeDirectiveParser(ExpressionParserType::EXPRESSION_ARGUMENTS)]
    public function translate(string $value, bool $escape = true): string
    {
        // $value is the verbatim expression the author wrote ('Hello' or $msg), embedded as-is.
        $translation = "__({$value})";

        return $escape ? "<?php echo \$escaper->escapeHtml({$translation}) ?>" : "<?php echo {$translation} ?>";
    }

    #[ScopeDirectiveParser(ExpressionParserType::EXPRESSION_ARGUMENTS)]
    public function child(string $alias): string
    {
        // $alias is the verbatim expression the author wrote ('sidebar' or $current), embedded as-is.
        return "<?php echo (\$block && \$block->getChildBlock({$alias})) ? \$block->getChildBlock({$alias})->toHtml() : '' ?>";
    }
}
