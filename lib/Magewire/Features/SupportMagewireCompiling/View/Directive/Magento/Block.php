<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\FunctionDirective;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveParser;
use Magewirephp\Magewire\Support\Php;

class Block extends FunctionDirective
{
    #[ScopeDirectiveParser(ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function child(string $alias): string
    {
        $alias = Php::stringLiteral($alias);

        return "<?php echo (\$block && \$block->getChildBlock({$alias})) ? \$block->getChildBlock({$alias})->toHtml() : '' ?>";
    }
}
