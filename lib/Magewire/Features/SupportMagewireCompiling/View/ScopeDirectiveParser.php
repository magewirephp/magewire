<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Attribute;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;

#[Attribute(Attribute::TARGET_METHOD)]
class ScopeDirectiveParser
{
    public function __construct(
        public ExpressionParserType $expressionParserType
    ) {
        //
    }
}
