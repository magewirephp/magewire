<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\DirectiveArea;

class MagewireCompiler extends Compiler
{
    protected array $compilers = [
        'Directives'
    ];

    protected function compileString(string $value): string
    {
        $this->optimizer()->pipe(function (string $throughput) {
            return $throughput . "<?php /**DATETIME " . date('Y-m-d H:i:s') . " ENDDATETIME*/ ?> \n";
        });

        return parent::compileString($value);
    }

    /**
     * Compile directives starting with "@".
     */
    protected function compileDirectives(string $template): string
    {
        preg_match_all('/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x', $template, $matches);

        $offset = 0;

        for ($i = 0; isset($matches[0][$i]); $i++) {
            $match = [
                $matches[0][$i],
                $matches[1][$i],
                $matches[2][$i],
                $matches[3][$i] ?: null,
                $matches[4][$i] ?: null,
            ];

            while (
                isset($match[4])
                && str_ends_with($match[0], ')')
                && ! $this->utils()->hasEvenNumberOfParentheses($match[0])
            ) {
                $after = strstr($template, $match[0]);

                if ($after === false) {
                    break;
                }

                $after = substr($after, strlen($match[0]));
                $pos = strpos($after, ')');

                if ($pos === false) {
                    break;
                }

                $rest = substr($after, 0, $pos);

                if (isset($matches[0][$i + 1]) && str_contains($rest . ')', $matches[0][$i + 1])) {
                    unset($matches[0][$i + 1]);
                    $i++;
                }

                $match[0] .= $rest . ')';
                $match[3] .= $rest . ')';
                $match[4] .= $rest;
            }

            [$template, $offset] = $this->replaceFirstStatement(
                $match[0],
                $this->compileDirective($match),
                $template,
                $offset
            );
        }

        return $template;
    }

    /**
     * Replace the first match for a statement compilation operation.
     */
    protected function replaceFirstStatement(string $search, string $replace, string $subject, int $offset): array|string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search, $offset);

        if ($position !== false) {
            return [
                substr_replace($subject, $replace, $position, strlen($search)),
                $position + strlen($replace),
            ];
        }

        return [$subject, 0];
    }

    /**
     * Compile a single "@" directive.
     */
    protected function compileDirective(array $match): string
    {
        [$area, $directive] = $this->manager()->directives()->tryToLocateArea($match[1]);

        if (str_contains($match[1], '@')) {
            $match[0] = isset($match[3]) ? $match[1] . $match[3] : $match[1];
        } elseif ($area instanceof DirectiveArea && is_string($directive) && $area->has($directive)) {
            $match[0] = $area->get($directive)->compile($match[4] ?? '', $directive);
        } elseif ($directive = $this->manager()->directives()->area()->get($directive)) {
            $match[0] = $directive->compile($match[4] ?? '', $match[1]);
        } else {
            return $match[0];
        }

        return isset($match[3]) ? $match[0] : $match[0] . $match[2];
    }
}
