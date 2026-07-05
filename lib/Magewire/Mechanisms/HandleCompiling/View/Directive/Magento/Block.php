<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\FunctionDirective;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\ScopeDirectiveParser;

class Block extends FunctionDirective
{
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function child(string $alias): string
    {
        return "<?php echo (\$block && \$block->getChildBlock('{$alias}')) ? \$block->getChildBlock('{$alias}')->toHtml() : '' ?>";
    }
}
