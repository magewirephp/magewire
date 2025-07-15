<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\FunctionExpressionParser;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;

abstract class FunctionDirective extends Directive
{
    public function compile(string $expression, string $directive): string
    {
        /** @var FunctionExpressionParser $parsed */
        $parsed = $this->parser(ExpressionParserType::FUNCTION_ARGUMENTS)->parse($expression);

        // TODO: should handle exceptions, logging them and return an empty string when so.
        return method_exists($this, $directive) ? $this->$directive(...$parsed->arguments()->all()) : '';
    }
}
