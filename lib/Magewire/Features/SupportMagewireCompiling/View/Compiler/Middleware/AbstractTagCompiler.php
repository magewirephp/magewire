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

/**
 * Shared machinery for prefix-based tag compilers (`<flux:button>`,
 * `<slot:test>`, `<magewire:counter>`, …).
 *
 * Subclasses declare a tag prefix and how to emit the opening directive; the
 * base class owns the regex assembly, the three-pass compile pipeline
 * (self-closing → opening → closing), the standard fragment-lifecycle
 * preamble, and the attribute-string → PHP-array parser.
 *
 * Adding a third-party prefix is one subclass + one DI entry on
 * `MagentoTemplateCompiler.middleware`. No framework code changes.
 */
abstract class AbstractTagCompiler implements ViewCompilerInterface
{
    /**
     * Atomic-quote attribute matcher shared by every tag-compiler middleware.
     *
     * Matches whitespace-separated `key`, `key="value"`, `key='value'`, raw
     * `@class(...)` / `@style(...)` directives, and `{{ $attributes }}`
     * spreads. Quoted values consume their surrounding quotes atomically so
     * spaces and embedded quotes survive the capture.
     */
    protected const ATTRIBUTES_PATTERN = <<<'REGEX'
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

    /**
     * The literal tag prefix that appears before the colon (`flux`, `slot`,
     * `magewire`, …). The base class regex-quotes this when assembling the
     * matching pattern, so subclasses just return the plain string.
     */
    abstract protected function prefix(): string;

    /**
     * Emit the compiled opening sequence for a matched tag.
     *
     * `$matches` carries the named captures (`variant`, `attributes`, plus
     * any subclass-specific extras). Implementations build their directive
     * call (`@magewireComponent(...)`, `@magewireSlot(...)`) and concatenate
     * with `$this->preamble(...)` for the shared `if isset → fill → distribute
     * → start → endif` block.
     */
    abstract protected function emitOpening(array $matches): string;

    /**
     * Regex fragment capturing the variant segment between `prefix:` and the
     * attributes block. Subclasses can narrow the alphabet — e.g. forbid
     * dots if the prefix doesn't allow dotted variants.
     */
    protected function variantPattern(): string
    {
        return '(?<variant>[\w\-.]+)';
    }

    /**
     * Directive emitted for a closing tag. Defaults to the component
     * directive; Slots overrides to `@magewireEndSlot`.
     */
    protected function closingDirective(): string
    {
        return '@magewireEndComponent';
    }

    /**
     * Regex used to find closing tags that should map to `closingDirective()`.
     * Override to accept additional shapes (Slots accepts bare `</slot>` in
     * addition to `</slot:name>` for Laravel-flavoured authoring).
     */
    protected function closingTagPattern(): string
    {
        $prefix = preg_quote($this->prefix(), '/');

        return "/<\/\s*{$prefix}:[\w\-.]+\s*>/";
    }

    final public function compile(string $value): string
    {
        if (! str_contains($value, '<' . $this->prefix() . ':')) {
            return $value;
        }

        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileOpeningTags($value);

        return $this->compileClosingTags($value);
    }

    /**
     * Standard fragment-lifecycle preamble emitted after the directive call.
     * Final because every compiler shares the same shape — fill dictionary,
     * distribute attributes, start buffering — and it's not a meaningful
     * extension point.
     */
    final protected function preamble(string $var, string $attributes): string
    {
        return "<?php if (isset(\${$var})): ?>
        <?php \${$var}->dictionary()->fill(get_defined_vars()) ?>
        <?php \${$var}->data()->distribute({$attributes}) ?>
        <?php \${$var}->start() ?>
        <?php endif ?>";
    }

    /**
     * Parse the captured attribute string into a PHP array literal mapping
     * to the `data()->distribute(...)` shape the Fragment expects. Quoted
     * values are captured atomically so spaces and embedded quotes survive;
     * non-prefixed string values are re-escaped via addcslashes when emitted
     * so the generated PHP is valid.
     *
     * Conventions:
     *   key="v"            → properties[key] = "v"
     *   :key="$expr"       → properties[key] = $expr
     *   bind:key="$expr"   → properties[key] = $expr
     *   ::key="v"          → attributes[key] = "v"   (literal HTML attr passthrough)
     *   magewire:foo="v"   → magewire["magewire:foo"] = "v"
     *   :magewire:foo=$x   → magewire["magewire:foo"] = $x
     *   bind:magewire:f=$x → magewire["magewire:f"] = $x
     */
    protected function parseParams(string $params): string
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

    protected function stripQuotes(string $value): string
    {
        return str_starts_with($value, '"') || str_starts_with($value, "'")
            ? substr($value, 1, -1)
            : $value;
    }

    protected function phpString(string $value): string
    {
        return '"' . addcslashes($value, "\"\\") . '"';
    }

    private function compileOpeningTags(string $value): string
    {
        return preg_replace_callback(
            $this->buildPattern(closing: false),
            fn (array $matches): string => $this->emitOpening($matches),
            $value
        );
    }

    private function compileSelfClosingTags(string $value): string
    {
        return preg_replace_callback(
            $this->buildPattern(closing: true),
            fn (array $matches): string => $this->emitOpening($matches) . "\n" . $this->closingDirective(),
            $value
        );
    }

    private function compileClosingTags(string $value): string
    {
        return preg_replace($this->closingTagPattern(), ' ' . $this->closingDirective(), $value);
    }

    /**
     * Assemble the matching regex from prefix() and variantPattern().
     * `$closing=true` adds the self-closing `\/>` suffix; otherwise the bare
     * `>` suffix matches an opening tag.
     */
    private function buildPattern(bool $closing): string
    {
        $prefix = preg_quote($this->prefix(), '/');
        $variant = $this->variantPattern();
        $attributes = self::ATTRIBUTES_PATTERN;
        $tail = $closing ? '\/>' : '>';

        return "/
            <\\s*
            {$prefix}
            :
            {$variant}
            {$attributes}
            (?<![\\/=\\-])
            {$tail}
        /x";
    }
}
