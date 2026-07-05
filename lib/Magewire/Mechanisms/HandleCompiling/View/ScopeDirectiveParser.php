<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View;

use Attribute;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser\ExpressionParserType;

#[Attribute(Attribute::TARGET_METHOD)]
class ScopeDirectiveParser
{
    public function __construct(
        public ExpressionParserType $expressionParserType
    ) {
    }
}
