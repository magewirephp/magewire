<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\ScopeDirectiveParser;

class Escape extends Directive
{
    // $expression is the verbatim expression the author wrote (@escape($url) / @escape('/path')),
    // embedded as-is and escaped here via $escaper.
    #[ScopeDirectiveParser(ExpressionParserType::EXPRESSION_ARGUMENTS)]
    public function url(string $expression): string
    {
        return "<?php echo \$escaper->escapeUrl({$expression}) ?>";
    }
}
