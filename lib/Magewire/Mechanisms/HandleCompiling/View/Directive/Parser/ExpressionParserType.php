<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser;

use Magento\Framework\App\ObjectManager;

/**
 * WIP: This enum currently behaves like a Factory but is intended to represent as an Enum type.
 *      This implementation is experimental and, due to its deep integration with the framework,
 *      it is assumed that most developers won't interact with it directly during development.
 *      As a result, this class is still subject to change.
 */
enum ExpressionParserType
{
    case CONDITION;
    case FUNCTION_ARGUMENTS;
    case ITERATION_CLAUSE;

    /**
     * Named/positional arguments whose values are kept verbatim as the PHP expressions the author
     * wrote (Blade-style), so `@child(alias: $current)` embeds `$current` and `@child('sidebar')`
     * embeds `'sidebar'`. Bare true/false/null become real bool/null for compile-time flags.
     *
     * SECURITY: values are embedded into compiled PHP as written, so this is for trusted template
     * (.phtml) source only — never wire it to user input. A directive using it is responsible for
     * escaping its own output (see the Escape directive's use of $escaper).
     */
    case EXPRESSION_ARGUMENTS;

    /**
     * Returns a new instance of a parse result.
     *
     * @template T of ExpressionParserType
     * @param array $arguments
     * @return ExpressionParser
     */
    public function create(array $arguments = []): ExpressionParser
    {
        return match ($this) {
            self::CONDITION, self::FUNCTION_ARGUMENTS, self::ITERATION_CLAUSE, self::EXPRESSION_ARGUMENTS => ObjectManager::getInstance()->create($this->getTypeClass(), $arguments)
        };
    }

    public function getTypeClass(): string
    {
        return match ($this) {
            self::CONDITION => ConditionExpressionParser::class,
            self::FUNCTION_ARGUMENTS => FunctionExpressionParser::class,
            self::ITERATION_CLAUSE => IterationClauseExpressionParser::class,
            self::EXPRESSION_ARGUMENTS => ExpressionArgumentsParser::class
        };
    }
}
