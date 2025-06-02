<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser;

use Magewirephp\Magewire\Support\Parser;

class FunctionArgumentsParser extends Parser
{
    private FunctionArguments|null $arguments = null;
    private string|null $expression = null;

    public function __construct(
        private readonly FunctionArgumentsFactory $functionArgumentsFactory
    ) {
        //
    }

    public function parse(string $content): self
    {
        $this->expression = $content;
        $this->arguments()->merge(strlen($content) === 0 ? [] : $this->parseArguments($content));

        return $this;
    }

    public function arguments(): FunctionArguments
    {
        return $this->arguments ??= $this->functionArgumentsFactory->create();
    }

    /**
     * Returns the original expression string.
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    private function parseArguments(string $expression): array
    {
        preg_match_all('/(\w+):\s*([^,]+)/', $expression, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $argument => $value) {
            $key = $value[0];
            $value = trim($matches[2][$argument][0]);

            // Determine the value type based on the value's format
            if (preg_match('/^\'(.*)\'$/', $value, $stringMatch)) {
                // Handle string: wrap in quotes
                $arguments[$key] = '\'' . $stringMatch[1] . '\'';
            } elseif (preg_match('/^\d+(\.\d+)?$/', $value)) {
                // Handle float or integer
                $arguments[$key] = is_float($value + 0) ? (float) $value : (int) $value;
            } elseif (strtolower($value) === 'true' || strtolower($value) === 'false') {
                // Handle boolean
                $arguments[$key] = strtolower($value) === 'true';
            } elseif (strtolower($value) === 'null') {
                // Handle null
                $arguments[$key] = null;
            } else {
                // Handle unrecognized values as raw strings
                $arguments[$key] = $value;
            }
        }

        return $arguments ?? [];
    }
}
