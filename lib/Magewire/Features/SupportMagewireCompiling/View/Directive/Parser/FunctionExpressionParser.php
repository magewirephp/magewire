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
     *
     * @throws InvalidArgumentException If parsing fails
     */
    public function parseArguments(string $expression): array
    {
        if (empty(trim($expression))) {
            return [];
        }

        // Check if the entire expression is a JSON string first.
        if ($this->isJsonString($expression)) {
            return $this->parseJsonArguments($expression);
        }

        $pattern = $this->buildPattern();

        if (! preg_match_all($pattern, $expression, $matches, PREG_OFFSET_CAPTURE)) {
            throw new InvalidArgumentException("Failed to parse expression: " . $expression);
        }

        $arguments = [];
        $errors = [];

        foreach ($matches[1] as $index => $keyMatch) {
            $key = trim($keyMatch[0]);

            // Validate key.
            if (empty($key) || !$this->isValidKey($key)) {
                $errors[] = "Invalid key at position {$keyMatch[1]}: '$key'";
                continue;
            }

            // Check if we have a corresponding value.
            if (! isset($matches[2][$index])) {
                $errors[] = "Missing value for key '$key'";
                continue;
            }

            $rawValue = trim($matches[2][$index][0]);
            $valuePosition = $matches[2][$index][1];

            try {
                $parsedValue = $this->parseValue($rawValue);
                $arguments[$key] = $parsedValue;
            } catch (Exception $e) {
                $errors[] = "Failed to parse value for key '$key' at position $valuePosition: " . $e->getMessage();
            }
        }

        // If we have errors but also some successful parses, you might want to log warnings
        // instead of throwing. Adjust based on your needs.
        if (! empty($errors) && empty($arguments)) {
            throw new InvalidArgumentException("Parsing failed with errors: " . implode(', ', $errors));
        }

        return $arguments;
    }

    /**
     * Build the regex pattern for matching key-value pairs
     *
     * @return string The complete regex pattern
     */
    private function buildPattern(): string
    {
        $stringPattern = '"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'';
        $numberPattern = '-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?';
        $booleanPattern = 'true|false';
        $nullPattern = 'null';

        // Improved nested structure patterns
        $objectPattern = $this->buildNestedPattern('{', '}');
        $arrayPattern = $this->buildNestedPattern('[', ']');

        // Fallback for unquoted values and variables
        $variablePattern = '\$[a-zA-Z_][a-zA-Z0-9_]*';
        $unquotedPattern = '[a-zA-Z_][a-zA-Z0-9_-]*';

        $valuePattern = implode('|', [
            $stringPattern,
            $objectPattern,
            $arrayPattern,
            $booleanPattern,
            $nullPattern,
            $numberPattern,
            $variablePattern,
            $unquotedPattern
        ]);

        return '/(\w+):\s*(' . $valuePattern . ')/';
    }

    /**
     * Build pattern for nested structures (objects/arrays)
     *
     * @param string $openChar Opening character ('{' or '[')
     * @param string $closeChar Closing character ('}' or ']')
     * @return string Pattern for nested structures
     */
    private function buildNestedPattern(string $openChar, string $closeChar): string
    {
        $escaped = [
            '{' => '\{', '}' => '\}',
            '[' => '\[', ']' => '\]'
        ];

        $open = $escaped[$openChar];
        $close = $escaped[$closeChar];
        $notOpenClose = '[^' . $openChar . $closeChar . ']';

        // This pattern handles 2-3 levels of nesting reasonably well
        // For deeper nesting, consider using a proper parser
        return $open . '(?:' . $notOpenClose . '*(?:' . $open . $notOpenClose . '*' . $close . $notOpenClose . '*)*)*' . $close;
    }

    /**
     * Validate if a key is a valid-named argument identifier.
     *
     * @param string $key The key to validate
     * @return bool True if valid named argument, false otherwise
     */
    private function isValidKey(string $key): bool
    {
        // Named arguments should be valid PHP identifiers
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key) === 1;
    }

    /**
     * Parse a value string into the appropriate PHP type.
     *
     * @throws InvalidArgumentException If value cannot be parsed
     */
    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException("Empty value");
        }

        if ($value === 'null') {
            return null;
        }

        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        // Handle quoted strings.
        if ($this->isQuotedString($value)) {
            return $this->parseQuotedString($value);
        }

        // Handle JSON objects and arrays.
        if ($this->isJsonStructure($value)) {
            return $this->parseJsonStructure($value);
        }

        // Handle numbers.
        if ($this->isNumber($value)) {
            return $this->parseNumber($value);
        }

        // Handle variables (starting with $).
        if ($this->isVariable($value)) {
            return $this->parseVariable($value);
        }

        // Handle unquoted strings (be careful with this).
        if ($this->isValidUnquotedString($value)) {
            return $value;
        }

        throw new InvalidArgumentException("Unable to parse value: $value");
    }

    /**
     * Check if the value is a quoted string.
     */
    private function isQuotedString(string $value): bool
    {
        return (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"));
    }

    /**
     * Parse quoted string, handling escape sequences.
     */
    private function parseQuotedString(string $value): string
    {
        $quote = $value[0];
        $content = substr($value, 1, -1);

        // Handle escape sequences.
        return str_replace(['\\' . $quote, '\\\\'], [$quote, '\\'], $content);
    }

    /**
     * Check if value is a JSON structure.
     */
    private function isJsonStructure(string $value): bool
    {
        return (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
            (str_starts_with($value, '[') && str_ends_with($value, ']'));
    }

    /**
     * Parse JSON structure.
     */
    private function parseJsonStructure(string $value)
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Check if value is a number.
     */
    private function isNumber(string $value): bool
    {
        return preg_match('/^-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?$/', $value) === 1;
    }

    /**
     * Parse number value.
     */
    private function parseNumber(string $value): float|int
    {
        if (str_contains($value, '.') || str_contains(strtolower($value), 'e')) {
            return (float) $value;
        }

        return (int) $value;
    }

    /**
     * Check if the expression is a JSON string.
     */
    private function isJsonString(string $expression): bool
    {
        $trimmed = trim($expression);

        if (! str_starts_with($trimmed, '{') || ! str_ends_with($trimmed, '}')) {
            return false;
        }

        json_decode($trimmed);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Parse JSON string into named arguments.
     */
    private function parseJsonArguments(string $jsonString): array
    {
        $decoded = json_decode(trim($jsonString), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }
        if (! is_array($decoded)) {
            throw new InvalidArgumentException("JSON must decode to an associative array");
        }

        $arguments = [];
        foreach ($decoded as $key => $value) {
            // Validate that keys are valid named argument identifiers
            if (! $this->isValidKey($key)) {
                throw new InvalidArgumentException("Invalid argument name in JSON: '$key'");
            }

            $arguments[$key] = $value;
        }

        return $arguments;
    }

    /**
     * Check if value is a variable reference.
     */
    private function isVariable(string $value): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $value) === 1;
    }

    /**
     * Parse variable reference - returns a special object to indicate it's a variable.
     */
    private function parseVariable(string $value): array
    {
        return [
            'type' => 'variable',
            'name' => substr($value, 1) // Remove the $ prefix
        ];
    }

    /**
     * Check if unquoted string is valid for named arguments.
     */
    private function isValidUnquotedString(string $value): bool
    {
        // Be restrictive about what we accept as unquoted strings in function expressions.
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $value) === 1;
    }
}
