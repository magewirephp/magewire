<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler\Middleware;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\Contracts\ViewCompilerInterface;
use Magewirephp\Magewire\Support\Random;

/**
 * Slot tag compiler — short-form `<slot:{name}>...</slot:{name}>` rewriter.
 *
 * Companion to {@see FluxTagCompiler}. Recognizes only the `slot:` prefix and
 * rewrites each occurrence into Magewire's existing `@magewireSlot` /
 * `@magewireEndSlot` scope directives, which expand (in
 * {@see \Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magewire\Slot})
 * to `$__magewire->factory()->elements()->slot('{name}', $block)`. The Slot
 * Fragment Element registers itself in the SlotsRegistry on `start()` and
 * captures buffered output on `end()`, making the captured content available
 * inside the surrounding component's render scope as `$__slot('{name}')`.
 *
 * Tags `<slot:{name}>...</slot:{name}>` only nest inside an active Fragment
 * Element (the parent flux/flake/magewire-prefixed tag). Outside such a scope
 * the SlotsRegistry has no area to register against — same constraint as the
 * underlying directive, this compiler does not enforce it.
 *
 * Closing tags accept both `</slot:{name}>` and the bare `</slot>` form so
 * authors may write either; both compile down to the same `@magewireEndSlot`.
 */
class Slots implements ViewCompilerInterface
{
    private const PREFIX = 'slot';

    private const ATTRIBUTES_PATTERN = <<<'REGEX'
        (?<attributes>
            (?:
                \s+
                (?:
                    @(?:class)\( (?: (?>[^()]+) | (?-1) )* \)
                    |
                    @(?:style)\( (?: (?>[^()]+) | (?-1) )* \)
                    |
                    \{\{\s*\$attributes(?:[^}]+?)?\s*\}\}
                    |
                    (:\$)(\w+)
                    |
                    [\w\-:.@%]+
                    (
                        =
                        (?:
                            "[^"]*"
                            |
                            '[^']*'
                            |
                            [^'"=<>]+
                        )
                    )?
                )
            )*
            \s*
        )
        REGEX;

    public function compile(string $value): string
    {
        if (! str_contains($value, '<' . self::PREFIX . ':')) {
            return $value;
        }

        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileOpeningTags($value);

        return $this->compileClosingTags($value);
    }

    private function compileOpeningTags(string $value): string
    {
        $prefix = self::PREFIX;
        $attributes = self::ATTRIBUTES_PATTERN;

        $pattern = "/
            <\s*
            {$prefix}
            :
            (?<name>[\w\-.]+)
            {$attributes}
            (?<![\/=\-])
            >
        /x";

        return preg_replace_callback(
            $pattern,
            fn (array $matches): string => $this->slotString(
                $matches['name'],
                $matches['attributes']
            ),
            $value
        );
    }

    private function compileSelfClosingTags(string $value): string
    {
        $prefix = self::PREFIX;
        $attributes = self::ATTRIBUTES_PATTERN;

        $pattern = "/
            <\s*
            {$prefix}
            :
            (?<name>[\w\-.]+)
            {$attributes}
            (?<![\/=\-])
            \/>
        /x";

        return preg_replace_callback(
            $pattern,
            fn (array $matches): string => $this->slotString(
                $matches['name'],
                $matches['attributes']
            ) . "\n@magewireEndSlot",
            $value
        );
    }

    private function compileClosingTags(string $value): string
    {
        $prefix = self::PREFIX;

        // Accept both `</slot:name>` and the bare `</slot>` (Laravel-style symmetry).
        return preg_replace(
            "/<\/\s*{$prefix}(?::[\w\-.]+)?\s*>/",
            ' @magewireEndSlot',
            $value
        );
    }

    /**
     * Emit the opening sequence: scope-directive call that materializes a `slot`
     * Fragment Element bound to the current `$block` plus the dictionary/data
     * priming the Slot's render expects. Mirrors {@see Blade::compileSlots}'s
     * shape so both prefixes flow through the same Fragment lifecycle and
     * downstream directive parser.
     */
    private function slotString(string $name, string $attributesRaw): string
    {
        $attributes = $this->parseParams($attributesRaw);
        $var = 'slot' . ucfirst(strtolower($name)) . ucfirst(Random::alphabetical(5, true));

        return "@magewireSlot(target: '{$name}', id: '{$var}')
        <?php if (isset(\${$var})): ?>
        <?php \${$var}->dictionary()->fill(get_defined_vars()) ?>
        <?php \${$var}->data()->distribute({$attributes}) ?>
        <?php \${$var}->start() ?>
        <?php endif ?>";
    }

    /**
     * @see FluxTagCompiler::parseParams Same conventions; kept inline pending a
     * shared AttributeParser extraction across Blade / PrefixedTags / Flux / Slot.
     */
    private function parseParams(string $params): string
    {
        preg_match_all(
            '/(?<key>[\w:.@%-]+)\s*=\s*(?<value>"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|[^\s>]+)/ms',
            $params,
            $matches
        );

        $bag = [];

        foreach ($matches['key'] as $i => $key) {
            $value = $this->stripQuotes($matches['value'][$i]);

            $boolValue = match ($value) {
                'false' => false,
                'true'  => true,
                default => null,
            };

            if (str_starts_with($key, ':magewire:')) {
                $bag['magewire'][] = '"' . substr($key, 1) . '" => ' . $value;
                continue;
            }
            if (str_starts_with($key, 'bind:magewire:')) {
                $bag['magewire'][] = '"' . substr($key, 5) . '" => ' . $value;
                continue;
            }
            if (str_starts_with($key, 'magewire:')) {
                $bag['magewire'][] = '"' . $key . '" => ' . $this->phpString($value);
                continue;
            }
            if (str_starts_with($key, '::')) {
                $bag['attributes'][] = '"' . substr($key, 2) . '" => ' . $this->phpString($value);
                continue;
            }
            if (str_starts_with($key, ':')) {
                $bag['properties'][] = '"' . substr($key, 1) . '" => ' . $value;
                continue;
            }
            if (str_starts_with($key, 'bind:')) {
                $bag['properties'][] = '"' . substr($key, 5) . '" => ' . $value;
                continue;
            }

            $filling = match ($boolValue) {
                true    => 'true',
                false   => 'false',
                default => $this->phpString($value),
            };

            $bag['properties'][] = '"' . $key . '" => ' . $filling;
        }

        $result = '[';

        foreach ($bag as $key => $value) {
            $result .= '"' . $key . '" => [' . implode(', ', $value) . '], ';
        }

        return rtrim($result, ', ') . ']';
    }

    private function stripQuotes(string $value): string
    {
        return str_starts_with($value, '"') || str_starts_with($value, "'")
            ? substr($value, 1, -1)
            : $value;
    }

    private function phpString(string $value): string
    {
        return '"' . addcslashes($value, "\"\\") . '"';
    }
}
