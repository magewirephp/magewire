<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser;

use Exception;
use InvalidArgumentException;

class FunctionExpressionParser extends ExpressionParser
{
    /**
     * Parse named arguments from a function expression.
     * Supports: key: value, key2: ['a' => 'b', "x-data" => "init()"]
     */
    public function parseArguments(string $expression): array
    {
        $expression = trim($expression);

        if ($expression === '') {
            return [];
        }

        // Fast path: entire expression is a valid JSON object.
        if ($this->isJsonString($expression)) {
            return $this->parseJsonArguments($expression);
        }

        // Main path: parse named arguments with complex values (including PHP arrays).
        return $this->parseNamedArguments($expression);
    }

    /**
     * Parse named arguments: key: value, key2: value2, ...
     */
    private function parseNamedArguments(string $expression): array
    {
        $arguments = [];
        $pos = 0;
        $length = strlen($expression);

        while ($pos < $length) {
            $pos = $this->skipWhitespace($expression, $pos);

            if ($pos >= $length) {
                break;
            }

            // Extract key (unquoted identifier: name, attributes, etc.).
            [$key, $pos] = $this->extractKey($expression, $pos);

            if ($key === null) {
                throw new InvalidArgumentException("Invalid or missing key near position {$pos}");
            }

            $pos = $this->skipWhitespace($expression, $pos);

            if ($pos >= $length || $expression[$pos] !== ':') {
                throw new InvalidArgumentException("Expected ':' after key '{$key}' near position {$pos}");
            }

            $pos++; // skip ':'.
            $pos = $this->skipWhitespace($expression, $pos);

            if ($pos >= $length) {
                throw new InvalidArgumentException("Missing value for key '{$key}'");
            }

            // Extract value — supports strings, JSON, and full PHP arrays.
            [$value, $pos] = $this->extractComplexValue($expression, $pos);

            $arguments[$key] = $value;

            $pos = $this->skipWhitespace($expression, $pos);

            // Optional comma separator.
            if ($pos < $length && $expression[$pos] === ',') {
                $pos++;
            }
        }

        return $arguments;
    }

    /**
     * Extract an identifier key (e.g. name, attributes)
     */
    private function extractKey(string $expression, int $pos): array
    {
        $start = $pos;
        while ($pos < strlen($expression) && preg_match('/[a-zA-Z0-9_]/', $expression[$pos])) {
            $pos++;
        }

        if ($pos === $start) {
            return [null, $pos];
        }

        $key = substr($expression, $start, $pos - $start);

        // Keep top-level keys strict (valid PHP identifiers).
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
            return [null, $pos];
        }

        return [$key, $pos];
    }

    /**
     * Extract complex value: quoted string, JSON {}, JSON [], or PHP array []
     */
    private function extractComplexValue(string $expression, int $pos): array
    {
        if ($pos >= strlen($expression)) {
            throw new InvalidArgumentException("Unexpected end of expression");
        }

        $char = $expression[$pos];

        if ($char === '"' || $char === "'") {
            return [$this->extractQuotedString($expression, $pos), $pos];
        }

        if ($char === '{' || $char === '[') {
            // Could be JSON or PHP array — try PHP array first if starts with [.
            if ($char === '[') {
                // Peek ahead to see if it looks like PHP array (contains =>).
                $peekPos = $pos + 1;
                $peekPos = $this->skipWhitespace($expression, $peekPos);
                if ($peekPos < strlen($expression) && ($expression[$peekPos] === '"' || $expression[$peekPos] === "'" || $expression[$peekPos] === '$')) {
                    // Likely PHP array — try to parse it.
                    try {
                        $tempPos = $pos;
                        $array = $this->extractPhpArray($expression, $tempPos);
                        return [$array, $tempPos];
                    } catch (Exception) {
                        // Fall through to JSON array.
                    }
                }
            }

            // JSON object/array.
            return [$this->extractJsonStructure($expression, $pos), $pos];
        }

        // Unquoted value (true, false, null, number, variable, unquoted string).
        return [$this->extractUnquotedValue($expression, $pos), $pos];
    }

    /**
     * Extract and evaluate a full PHP array literal: ['class' => 'btn', "x-data" => "init()"]
     */
    private function extractPhpArray(string $expression, int &$pos): array
    {
        $start = $pos;
        $depth = 0;
        $inString = false;
        $escape = false;
        $quoteChar = null;

        $pos++; // skip opening [.

        while ($pos < strlen($expression)) {
            $char = $expression[$pos];

            if ($escape) {
                $escape = false;
                $pos++;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                $pos++;
                continue;
            }

            if ($char === '"' || $char === "'") {
                if (!$inString) {
                    $inString = true;
                    $quoteChar = $char;
                } elseif ($quoteChar === $char) {
                    $inString = false;
                    $quoteChar = null;
                }
                $pos++;
                continue;
            }

            if (!$inString) {
                if ($char === '[') {
                    $depth++;
                } elseif ($char === ']') {
                    if ($depth === 0) {
                        $pos++; // include closing ].
                        $arrayStr = substr($expression, $start, $pos - $start);
                        return $this->evaluatePhpArray($arrayStr);
                    }
                    $depth--;
                }
            }

            $pos++;
        }

        throw new InvalidArgumentException("Unclosed PHP array starting at position {$start}");
    }

    /**
     * Safely evaluate a PHP array literal using eval in a controlled way
     */
    private function evaluatePhpArray(string $arrayStr): array
    {
        $code = "return {$arrayStr};";

        $result = @eval($code);

        if ($result === false && $arrayStr !== '[]') {
            throw new InvalidArgumentException("Failed to parse PHP array: syntax error in {$arrayStr}");
        }

        if (!is_array($result)) {
            throw new InvalidArgumentException("Expression did not evaluate to an array: {$arrayStr}");
        }

        return $result;
    }

    /**
     * Extract JSON object { ... } or array [ ... ]
     */
    private function extractJsonStructure(string $expression, int &$pos): array
    {
        $start = $pos;
        $opening = $expression[$pos];
        $closing = $opening === '{' ? '}' : ']';
        $depth = 0;
        $inString = false;
        $escape = false;

        $pos++;

        while ($pos < strlen($expression)) {
            $char = $expression[$pos];

            if ($escape) {
                $escape = false;
            } elseif ($char === '\\') {
                $escape = true;
            } elseif ($char === '"') {
                $inString = !$inString;
            } elseif (!$inString) {
                if ($char === $opening) {
                    $depth++;
                } elseif ($char === $closing) {
                    if ($depth === 0) {
                        $pos++;
                        $json = substr($expression, $start, $pos - $start);
                        $decoded = json_decode($json, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
                        }
                        return $decoded;
                    }
                    $depth--;
                }
            }
            $pos++;
        }

        throw new InvalidArgumentException("Unclosed JSON structure starting at position {$start}");
    }

    /**
     * Extract quoted string
     */
    private function extractQuotedString(string $expression, int &$pos): string
    {
        $quote = $expression[$pos];
        $pos++;
        $start = $pos;
        $escape = false;

        while ($pos < strlen($expression)) {
            $char = $expression[$pos];

            if ($escape) {
                $escape = false;
                $pos++;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                $pos++;
                continue;
            }

            if ($char === $quote) {
                $value = substr($expression, $start, $pos - $start);
                $pos++;
                return str_replace(['\\' . $quote, '\\\\'], [$quote, '\\'], $value);
            }

            $pos++;
        }

        throw new InvalidArgumentException("Unclosed quoted string");
    }

    /**
     * Extract unquoted value (true, false, null, number, variable, simple string)
     */
    private function extractUnquotedValue(string $expression, int &$pos): mixed
    {
        $start = $pos;

        while ($pos < strlen($expression) && !in_array($expression[$pos], [',', '}', ']', ')', ' '], true)) {
            $pos++;
        }

        $value = trim(substr($expression, $start, $pos - $start));

        if ($value === '') {
            throw new InvalidArgumentException("Empty unquoted value");
        }

        return match (strtolower($value)) {
            'null' => null,
            'true' => true,
            'false' => false,
            default => $this->parseNumberOrVariableOrString($value),
        };
    }

    private function parseNumberOrVariableOrString(string $value): mixed
    {
        if ($this->isNumber($value)) {
            return $this->parseNumber($value);
        }

        if ($this->isVariable($value)) {
            return $this->parseVariable($value);
        }

        return $value; // unquoted string.
    }

    private function skipWhitespace(string $expression, int $pos): int
    {
        while ($pos < strlen($expression) && ctype_space($expression[$pos])) {
            $pos++;
        }
        return $pos;
    }

    private function isJsonString(string $expression): bool
    {
        $trimmed = trim($expression);
        if (!str_starts_with($trimmed, '{') || !str_ends_with($trimmed, '}')) {
            return false;
        }

        json_decode($trimmed);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function parseJsonArguments(string $jsonString): array
    {
        $decoded = json_decode(trim($jsonString), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException("JSON must decode to an array");
        }

        // No more strict key validation — allows 'x-data', 'aria-*', etc.
        return $decoded;
    }

    private function isNumber(string $value): bool
    {
        return preg_match('/^-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?$/', $value) === 1;
    }

    private function parseNumber(string $value): float|int
    {
        return str_contains($value, '.') || str_contains(strtolower($value), 'e')
            ? (float) $value
            : (int) $value;
    }

    private function isVariable(string $value): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $value) === 1;
    }

    private function parseVariable(string $value): array
    {
        return [
            'type' => 'variable',
            'name' => substr($value, 1),
        ];
    }
}
