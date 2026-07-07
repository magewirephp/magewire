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
    // RAW passthrough: $expression is trusted template source (see ExpressionParserType::RAW).
    // Output is escaped here via $escaper.
    #[ScopeDirectiveParser(ExpressionParserType::RAW)]
    public function url(string $expression): string
    {
        return "<?php echo \$escaper->escapeUrl({$expression}) ?>";
    }
}