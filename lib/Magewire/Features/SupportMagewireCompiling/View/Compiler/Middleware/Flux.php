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
 * Flux-flavored tag compiler for Magewire templates.
 *
 * Inspired by Laravel Flux's `FluxTagCompiler`, but specialized for the Magewire
 * runtime. Recognizes only the `flux:` prefix — `<flux:button>`, `<flux:input/>`,
 * `<flux:card>...</flux:card>` — and rewrites each occurrence into Magewire's
 * existing `@magewireComponent` / `@magewireEndComponent` scope directives. Those
 * directives expand (in {@see \Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magewire\Component})
 * to `$__magewire->factory()->elements()->element('flux', $block, $variant)`, which
 * resolves a `flux` Fragment Element (a {@see \Magewirephp\Magewire\Model\View\Fragment\Element}
 * subclass wired in DI). The Fragment lifecycle handles output buffering,
 * dictionary fill, attribute distribution, modifier/validator pipeline and final
 * render — no raw `ob_start` here, no parallel buffering scheme.
 *
 * Tag rewriting itself is regex-based and intentionally cheap: opening, closing
 * and self-closing forms each match independently. Nested `<flux:*>` resolve via
 * the surrounding directive scope chain (Fragment Element scoping handles the
 * stack), so this compiler does not need to walk balanced pairs.
 *
 * Flux is registered as Fragment Element type `flux` in di.xml (virtual type over
 * {@see \Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Fragment\Element\Flake}
 * with `FluxFlakeFactory` injected). Adding new Flux components is purely a
 * matter of providing layout blocks for the `magewire_flux` handle.
 */
class Flux implements ViewCompilerInterface
{
    private const PREFIX = 'flux';

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
            (?<variant>[\w\-.]+)
            {$attributes}
            (?<![\/=\-])
            >
        /x";

        return preg_replace_callback(
            $pattern,
            fn (array $matches): string => $this->componentString(
                $matches['variant'],
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
            (?<variant>[\w\-.]+)
            {$attributes}
            (?<![\/=\-])
            \/>
        /x";

        return preg_replace_callback(
            $pattern,
            fn (array $matches): string => $this->componentString(
                $matches['variant'],
                $matches['attributes']
            ) . "\n@magewireEndComponent",
            $value
        );
    }

    private function compileClosingTags(string $value): string
    {
        $prefix = self::PREFIX;

        return preg_replace(
            "/<\/\s*{$prefix}:[\w\-.]+\s*>/",
            ' @magewireEndComponent',
            $value
        );
    }

    /**
     * Emit the opening sequence: a scope-directive call that materializes a `flux`
     * Fragment Element bound to the current `$block`, plus the dictionary/data
     * priming the Fragment's render expects. The shape mirrors {@see Blade::componentString}
     * so both prefixes flow through the same Fragment lifecycle and downstream
     * directive parser.
     */
    private function componentString(string $variant, string $attributesRaw): string
    {
        $attributesRaw = preg_replace_callback(
            '/\s+slot\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^\s>]+)/',
            function (array $matches) use (&$slotTarget): string {
                $slotTarget = $this->stripQuotes($matches['value']);
                return '';
            },
            $attributesRaw,
            1
        );

        $attributes = $this->parseParams($attributesRaw);
        $id = Random::alphabetical(5, true);
        $var = 'flux' . ucfirst(strtolower($variant)) . ucfirst($id);
        $var = preg_replace('/[^a-zA-Z0-9]/', '_', $var);

        return "@magewireComponent(type: '" . self::PREFIX . "', id: '{$id}', variable: '{$var}' variant: '{$variant}')
        <?php if (isset(\${$var})): ?>
        <?php \${$var}->dictionary()->fill(get_defined_vars()) ?>
        <?php \${$var}->data()->distribute({$attributes}) ?>
        <?php \${$var}->start() ?>
        <?php endif ?>";
    }

    /**
     * Parse the captured attribute string into a PHP array literal mapping to the
     * `data()->distribute(...)` shape the Fragment expects. Quoted values are
     * captured atomically so spaces and embedded quotes survive; wrapping quotes
     * are peeled and non-prefixed string values are re-escaped via addcslashes
     * when emitted so the generated PHP is valid.
     *
     * Conventions:
     *   key="v"            → properties[key] = "v"
     *   :key="$expr"       → properties[key] = $expr
     *   bind:key="$expr"   → properties[key] = $expr
     *   ::key="v"          → attributes[key] = "v"  (literal HTML attr passthrough)
     *   magewire:foo="v"   → magewire["magewire:foo"] = "v"
     *   :magewire:foo=$x   → magewire["magewire:foo"] = $x
     *   bind:magewire:f=$x → magewire["magewire:f"] = $x
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
