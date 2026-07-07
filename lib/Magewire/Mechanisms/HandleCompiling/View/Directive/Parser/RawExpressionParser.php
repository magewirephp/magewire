<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser;

/**
 * Passthrough parser: performs no argument parsing at all.
 *
 * A RAW directive receives the verbatim expression as a single string. Directive::compile
 * short-circuits on this type and hands the raw expression straight to the directive method,
 * so this parser never actually runs; it exists to keep ExpressionParserType exhaustive.
 */
class RawExpressionParser extends ExpressionParser
{
    protected function parseArguments(string $expression): array
    {
        return [];
    }
}