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
     * Passthrough: the verbatim expression is embedded into the generated PHP and executed as-is,
     * with NO parsing, quoting, or escaping.
     *
     * SECURITY: only ever use RAW for expressions that come from trusted template (.phtml) source
     * authored by developers. Never wire a RAW directive to anything that can carry user input or
     * request data — that would compile attacker-controlled text into executable PHP. Directives
     * that accept dynamic values must use a parsing type (e.g. FUNCTION_ARGUMENTS), which quotes
     * and escapes their arguments. The RAW directive is itself responsible for escaping its output
     * (see the Escape directive's use of $escaper).
     */
    case RAW;

    /**
     * Returns a new instance of a parse result.
     *
     * @template T of ExpressionParserType
     * @param array $arguments
     * @return ExpressionParser
     */
    public function create(array $arguments = []): ExpressionParser
    {
        return match ($this) { self::CONDITION, self::FUNCTION_ARGUMENTS, self::ITERATION_CLAUSE, self::RAW => ObjectManager::getInstance()->create($this->getTypeClass(), $arguments) };
    }

    public function getTypeClass(): string
    {
        return match ($this) {
            self::CONDITION => ConditionExpressionParser::class,
            self::FUNCTION_ARGUMENTS => FunctionExpressionParser::class,
            self::ITERATION_CLAUSE => IterationClauseExpressionParser::class,
            self::RAW => RawExpressionParser::class
        };
    }
}
