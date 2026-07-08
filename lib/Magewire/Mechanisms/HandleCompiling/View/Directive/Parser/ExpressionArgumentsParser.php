<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Parser;

use InvalidArgumentException;

/**
 * Parses directive arguments Blade-style: each value is kept verbatim as the PHP expression the
 * author wrote, so it can be embedded directly into the compiled output.
 *
 *   @child('sidebar')          -> ['sidebar']            (positional)
 *   @child(alias: 'sidebar')   -> ['alias' => 'sidebar'] (named)
 *   @child(alias: $current)    -> ['alias' => '$current']
 *
 * Values keep their quotes and "$"; nested quotes, brackets and parentheses are respected when
 * finding argument boundaries, so complex expressions (arrays, method calls, ternaries) survive
 * intact. The only values that are converted are the bare keywords true/false/null — returned as
 * real bool/null so directives can use them for compile-time decisions (e.g. an escape flag).
 *
 * @mago-expect lint:cyclomatic-complexity
 */
class ExpressionArgumentsParser extends ExpressionParser
{
    public function parseArguments(string $expression): array
    {
        $arguments = [];
        $pos = 0;
        $index = 0;
        $length = strlen($expression);

        while ($pos < $length) {
            $pos = $this->skipWhitespace($expression, $pos);

            if ($pos >= $length) {
                break;
            }

            // Optional "name:" prefix (but not a "::" static reference). Anything else is positional.
            $key = null;

            if (preg_match('/^([a-zA-Z_]\w*)\s*:(?!:)/', substr($expression, $pos), $matches) === 1) {
                $key = $matches[1];
                $pos = $this->skipWhitespace($expression, $pos + strlen($matches[0]));
            }

            [$value, $pos] = $this->captureValue($expression, $pos);

            if ($value === '') {
                throw new InvalidArgumentException('Empty directive argument.');
            }

            $value = $this->typed($value);

            if ($key !== null) {
                $arguments[$key] = $value;
            } else {
                $arguments[$index++] = $value;
            }

            $pos = $this->skipWhitespace($expression, $pos);

            if ($pos < $length && $expression[$pos] === ',') {
                $pos++;
            }
        }

        return $arguments;
    }

    /**
     * Capture a single value verbatim, up to the next top-level comma, respecting string literals
     * and bracket/parenthesis nesting so separators inside a value are not mistaken for boundaries.
     */
    private function captureValue(string $expression, int $pos): array
    {
        $start = $pos;
        $length = strlen($expression);
        $depth = 0;
        $quote = null;
        $escape = false;

        while ($pos < $length) {
            $char = $expression[$pos];

            if ($escape) {
                $escape = false;
            } elseif ($quote !== null) {
                if ($char === '\\') {
                    $escape = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
            } elseif ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '[' || $char === '(' || $char === '{') {
                $depth++;
            } elseif ($char === ']' || $char === ')' || $char === '}') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                break;
            }

            $pos++;
        }

        return [trim(substr($expression, $start, $pos - $start)), $pos];
    }

    private function typed(string $value): string|bool|null
    {
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value
        };
    }

    private function skipWhitespace(string $expression, int $pos): int
    {
        while ($pos < strlen($expression) && ctype_space($expression[$pos])) {
            $pos++;
        }

        return $pos;
    }
}
