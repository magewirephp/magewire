<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler\Middleware;

use Magewirephp\Magewire\Support\Random;

/**
 * Blade-inspired template compiler middleware.
 *
 * This is an initial implementation with compilation logic embedded directly in the middleware.
 * In a future iteration, the compilation logic should be extracted into a dedicated, standalone
 * compiler class following single responsibility principles. The middleware would then delegate
 * to that compiler rather than handling compilation itself.
 *
 * Note: if a standalone package already exists, there is no point in writing one ourselves.
 *
 * @todo Refactor: Extract compilation logic into a separate BladeCompiler class
 * @todo Decouple middleware from compilation concerns for better testability and reusability
 * @todo Maybe make this compiler a separate package with only the compiling methods, excluding file system features.
 */
class Blade
{
    /**
     * Compile the component and slot tags within the given string.
     */
    public function compile(string $value): string
    {
        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileSlots($value);
        $value = $this->compileOpeningTags($value);
        return $this->compileClosingTags($value);
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
            $attributes = $this->parseParams($matches['attributes']);

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
            $attributes = $this->parseParams($matches['attributes']);

            return $this->componentString($matches['component'], $attributes, $matches['variant']) . "\n@endComponent";
        }, $value);
    }

    protected function componentString(string $component, string $attributes = '[]', string|null $variant = 'default'): string
    {
        $variant ??= 'default';
        $var = 'component' . ucfirst(strtolower($variant)) . ucfirst(Random::alphabetical(5, true));

        return "@magewireComponent(type: '{$component}', id: '{$var}', variant: '{$variant}')
        <?php if (isset(\${$var})): ?>
        <?php \${$var}->dictionary()->fill(get_defined_vars()) ?>
        <?php \${$var}->data()->distribute({$attributes}) ?>
        <?php \${$var}->start() ?>
        <?php endif ?>";
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
            $name = $this->stripQuotes($matches['inlineName']
                ?: $matches['name']
                    ?: $matches['boundName'])
                ?: "'slot'";

            $attributes = $this->parseParams($matches['attributes']);
            $var = 'slot' . ucfirst(strtolower($name)) . ucfirst(Random::alphabetical(5, true));

            return "@magewireSlot(target: '{$name}', id: '{$var}')
            <?php if (isset(\${$var})): ?>
            <?php \${$var}->dictionary()->fill(get_defined_vars()) ?>
            <?php \${$var}->data()->distribute({$attributes}) ?>
            <?php \${$var}->start() ?>
            <?php endif ?>";
        }, $value);

        return preg_replace('/<\/\s*magewire-slot[^>]*>/', ' @magewireEndSlot', $value);
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
     * @tddo Minimal implementation, needs a lot more work to make edge cases work
     *       For example, Dynamic classes based on a template variable.
     */
    protected function parseParams($params): string
    {
        preg_match_all('/([a-zA-Z0-9:-]*?)\s*?=\s*?(.+?)(\s|$)/ms', $params, $matches);
        $params = [];

        foreach ($matches[1] as $i => $key) {
            $value = str_replace('"', '', $matches[2][$i]);

            $value = match($value) {
                'false' => false,
                'true'  => true,

                default => $value
            };

            if (str_starts_with($key, ':magewire:')) {
                $params['magewire'][] = '"' . substr($key, 1) . '" => ' . $value;
                continue;
            }
            if (str_starts_with($key, 'bind:magewire:')) {
                $params['magewire'][] = '"' . substr($key, 5) . '" => ' . $value;
                continue;
            }
            if (str_starts_with($key, 'magewire:')) {
                $params['magewire'][] = '"' . $key . '" => "' . $value . '"';
                continue;
            }
            if (str_starts_with($key, '::')) {
                $params['attributes'][] = '"' . substr($key, 2) . '" => "' . $value . '"';
                continue;
            }
            if (str_starts_with($key, ':')) {
                $params['properties'][] = '"' . substr($key, 1) . '" => ' . $value;
                continue;
            }
            if (str_starts_with($key, 'bind:')) {
                $params['properties'][] = '"' . substr($key, 5) . '" => ' . $value;
                continue;
            }

            $filling = is_bool($value) ? ($value ? 'true' : 'false') : '"' . $value . '"';
            $params['properties'][] = '"' . $key . '"' . ' => ' . $filling;
        }

        $result = '[';

        foreach ($params as $key => $value) {
            $result .= '"' . $key . '" => [' . implode(', ', $value) . '], ';
        }

        $result = rtrim($result, ', ');
        $result .= ']';

        return $result;
    }
}
