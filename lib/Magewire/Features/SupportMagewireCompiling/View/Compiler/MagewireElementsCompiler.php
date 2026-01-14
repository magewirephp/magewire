<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\MagewireElementAttributes;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\MagewireElementAttributesFactory;
use Magewirephp\Magewire\Support\Random;

class MagewireElementsCompiler
{
    public function __construct(
        private MagewireElementAttributesFactory $magewireElementAttributesFactory
    ) {
        //
    }

    /**
     * Compile the component and slot tags within the given string.
     */
    public function compile(string $value): string
    {
        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileSlots($value);
        $value = $this->compileOpeningTags($value);
        $value = $this->compileClosingTags($value);

        return $value;
    }

    /**
     * Compile the opening tags within the given string.
     */
    protected function compileOpeningTags(string $value): string
    {
        $pattern = "/
            <\s*
            magewire-
            (?<component>[\w\-:]+)
            :
            (?<variant>[\w\-]+)
            (?<attributes>
                (?:
                    \s+
                    (?:
                        @(?:class)\( (?: (?>[^()]+) | (?-1) )* \)
                        |
                        @(?:style)\( (?: (?>[^()]+) | (?-1) )* \)
                        |
                        \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                        |
                        (:\\\$)(\w+)
                        |
                        [\w\-:.@%]+
                        (
                            =
                            (?:
                                \\\"[^\\\"]*\\\"
                                |
                                \'[^\']*\'
                                |
                                [^\'\\\"=<>]+
                            )
                        )?
                    )
                )*
                \s*
            )
            (?<![\/=\-])
        >/x";

        return preg_replace_callback($pattern, function (array $matches) {
            $attributes = $this->convertAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches['component'], $attributes, $matches['variant'] ?? null);
        }, $value);
    }

    /**
     * Compile the self-closing tags within the given string.
     */
    protected function compileSelfClosingTags(string $value): string
    {
        $pattern = "/
            <\s*
            magewire-
            (?<component>[\w\-:]+)
            :
            (?<variant>[\w\-]+)
            (?<attributes>
                (?:
                    \s+
                    (?:
                        @(?:class)\( (?: (?>[^()]+) | (?-1) )* \)
                        |
                        @(?:style)\( (?: (?>[^()]+) | (?-1) )* \)
                        |
                        \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                        |
                        (:\\\$)(\w+)
                        |
                        [\w\-:.@%]+
                        (
                            =
                            (?:
                                \\\"[^\\\"]*\\\"
                                |
                                \'[^\']*\'
                                |
                                [^\'\\\"=<>]+
                            )
                        )?
                    )
                )*
                \s*
            )
            (?<![\/=\-])
        \/>/x";

        return preg_replace_callback($pattern, function (array $matches) {
            $attributes = $this->convertAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches['component'], $attributes, $matches['variant']) . "\n@endComponent";
        }, $value);
    }

    protected function componentString(string $component, MagewireElementAttributes $attributes, string|null $variant = 'default'): string
    {
        $var = Random::alphabetical(10);
        $variant = $variant ?? 'default';

        return "@magewireComponent(type: '{$component}', id: '{$var}', variant: '{$variant}')
        <?php \${$var}->withTemplateData(get_defined_vars()) ?>
        <?php \${$var}->withAttributes([{$attributes}]) ?>
        <?php \${$var}->start() ?>";
    }

    /**
     * Compile the closing tags within the given string.
     */
    protected function compileClosingTags(string $value): string
    {
        return preg_replace("/<\/\s*magewire-[\:]?[\w\-\:\.]*\s*>/", ' @magewireEndComponent', $value);
    }

    /**
     * Compile the slot tags within the given string.
     */
    public function compileSlots(string $value): string
    {
        $pattern = "/
            <
                \s*
                magewire-slot
                (?:\:(?<inlineName>\w+(?:-\w+)*))?
                (?:\s+name=(?<name>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?
                (?:\s+\:name=(?<boundName>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        $value = preg_replace_callback($pattern, function ($matches) {
            $name = $this->stripQuotes($matches['inlineName'] ?: $matches['name'] ?: $matches['boundName']) ?: "'slot'";

            if (str_contains($name, '-') && ! empty($matches['inlineName'])) {
                $name = $this->camel($name);
            }
            if (! empty($matches['inlineName']) || ! empty($matches['name'])) {
                $name = "{$name}";
            }

            $attributes = $this->convertAttributesFromAttributeString($matches['attributes']);
            $var = Random::alphabetical(10);

            return "@magewireSlot(target: '{$name}', id: '{$var}')
                <?php \${$var}->withTemplateData(get_defined_vars()) ?>
                <?php \${$var}->withAttributes([{$attributes}]) ?>
                <?php \${$var}->start() ?>";
        }, $value);

        return preg_replace('/<\/\s*magewire-slot[^>]*>/', ' @magewireEndSlot', $value);
    }

    /**
     * Get an array of attributes from the given attribute string.
     */
    protected function convertAttributesFromAttributeString(string $attributeString): MagewireElementAttributes
    {
        $attributes = $this->magewireElementAttributesFactory->create();

        $attributeString = $this->parseShortAttributeSyntax($attributeString);
        $attributeString = $this->parseAttributeBag($attributeString);
        $attributeString = $this->parseBindAttributes($attributeString);
        $attributeString = $this->parseMagewireAttributes($attributeString);

        $pattern = '/
            (?<attribute>[\w\-:.@%]+)
            (
                =
                (?<value>
                    (
                        \"[^\"]+\"
                        |
                        \\\'[^\\\']+\\\'
                        |
                        [^\s>]+
                    )
                )
            )?
        /x';

        if (! preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $attribute = trim($match['attribute']);
            $rawValue = $match['value'] ?? null;

            $normalized = $this->normalizeAttributeValue($rawValue);

            if ($rawValue === null) {
                $normalized = true;
            }

            match (true) {
                str_starts_with($attribute, 'magewire:') => $attributes->magewire()->set($attribute, $normalized),
                str_starts_with($attribute, '::')        => $attributes->html()->set(substr($attribute, 2), $normalized),
                str_starts_with($attribute, ':')         => $attributes->bindings()->set(substr($attribute, 1), $normalized),
                str_starts_with($attribute, 'bind:')     => $attributes->bindings()->set(substr($attribute, 5), $normalized),

                default                                  => $attributes->properties()->set($attribute, $normalized),
            };
        }

        return $attributes;
    }

    protected function normalizeAttributeValue(string|null $raw): string|bool|null
    {
        if ($raw === null) {
            return null; // shorthand attribute without = → treat as true later if needed
        }

        $value = trim($raw);

        // Remove matching surrounding quotes (single or double)
        if (preg_match('/^([\'"])(.*)\1$/s', $value, $m)) {
            $value = $m[2];
        }

        // Handle common literals
        return match (strtolower($value)) {
            'true'   => true,
            'false'  => false,
            'null'   => null,
            default  => $value, // keep as-is: could be expression or plain string
        };
    }

    /**
     * Parses a short attribute syntax like :$foo into a fully-qualified syntax.
     */
    protected function parseShortAttributeSyntax(string $value): string
    {
        $pattern = "/\s\:\\\$(\w+)/x";

        return preg_replace_callback($pattern, function (array $matches) {
            return " :{$matches[1]}=\"\${$matches[1]}\"";
        }, $value);
    }

    /**
     * Parse the "magewire" attributes in a given attribute string.
     */
    protected function parseMagewireAttributes(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)
            :(?!:)
            ([\w\-:.@]+)
            =
        /xm";

        return preg_replace($pattern, ' magewire:$1=', $attributeString);
    }

    /**
     * Parse the attribute bag in a given attribute string.
     */
    protected function parseAttributeBag(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)
            \{\{\s*(\\\$attributes(?:[^}]+?(?<!\s))?)\s*\}\}
        /x";

        return preg_replace($pattern, ' :attributes="$1"', $attributeString);
    }

    /**
     * Parse the "bind" attributes in a given attribute string.
     */
    protected function parseBindAttributes(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)
            :(?!:)
            ([\w\-:.@]+)
            =
        /xm";

        return preg_replace($pattern, ' bind:$1=', $attributeString);
    }

    protected function isPhpExpression(string $value): bool
    {
        $value = trim($value);

        // Duidelijke PHP variabelen / expressies
        if (preg_match('/^\$[a-zA-Z_]|->|\?->|\?\?|\(|\[|\]|\{|\}|::|\band\b|\bor\b|\bnot\b|\bin\b|\binstanceof\b/', $value)) {
            return true;
        }

        // Arrays / object literals die PHP code lijken
        if (preg_match('/^[\[\{]/', $value) && preg_match('/[\]\}]$/', $value)) {
            return true;
        }

        // Geen pure string meer na strippen
        if (!preg_match('/^[\'"].*[\'"]$/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Strip any quotes from the given string.
     */
    public function stripQuotes(string $value): string
    {
        return (str_starts_with($value, '"') || str_starts_with($value, "'"))
            ? substr($value, 1, -1)
            : $value;
    }

    /**
     * Convert string to camelCase.
     */
    protected function camel(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value))));
    }

    /**
     * Get the portion of a string after the last occurrence of a given value.
     */
    protected function afterLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    /**
     * Map array keys to camelCase.
     */
    protected function mapKeysToCamelCase(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[$this->camel($key)] = $value;
        }

        return $result;
    }
}
