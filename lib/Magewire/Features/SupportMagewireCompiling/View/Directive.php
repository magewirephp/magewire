<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParser;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Support\Random;
use ReflectionClass;
use ReflectionMethod;

abstract class Directive
{
    private array $expressionParsers = [];
    private array $variables = [];

    /**
     * Compiles a string-based directive into executable code.
     *
     * This method processes readable string directives and transforms them into functional code,
     * depending on the directive's nature. The output of a directive can vary. Typically, the method
     * checks for underlying functions that can act as child compilers, allowing a single compiler
     * to handle multiple directives.
     *
     * @param string $expression The expression associated with the directive.
     * @param string $directive The name of the directive to compile.
     */
    public function compile(string $expression, string $directive)
    {
        if (method_exists($this, $directive) && $type = $this->getExpressionParserFor($directive)) {
            $parser = $this->parser($type)->parse($expression);

            $allArgs = $parser->arguments()->all();
            $method = new ReflectionMethod($this, $directive);
            $args = [];

            foreach ($method->getParameters() as $param) {
                $paramName = $param->getName();

                if (array_key_exists($paramName, $allArgs)) {
                    $args[$paramName] = $allArgs[$paramName];
                }
            }

            return $this->{$directive}(...$args);
        }

        return $this->{$directive}();
    }

    protected function parser(ExpressionParserType $parser, array $arguments = []): ExpressionParser
    {
        return $parser->create($arguments);
    }

    protected function getExpressionParserFor(string $directive): ExpressionParserType|null
    {
        $directive = implode('::', [static::class, $directive]);

        if (! ($this->expressionParsers[$directive] ?? null)) {
            $reflection = new ReflectionClass($this);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(ScopeDirectiveParser::class);
                $attribute  = ($attributes[0] ?? null) ? $attributes[0]->newInstance() : null;

                if ($attribute) {
                    $this->expressionParsers[implode('::', [static::class, $method->getName()])] = $attribute->expressionParserType;
                }
            }
        }

        return $this->expressionParsers[$directive] ?? null;
    }

    protected function var(string $name, bool $pop = false): string
    {
        $var = $this->variables[$name] ??= Random::alphabetical(10, true);

        if ($pop) {
            unset($this->variables[$name]);
        }

        return $var;
    }
}
