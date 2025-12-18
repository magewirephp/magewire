<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Compiler;

use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\Parser\DomElementParser;
use Magewirephp\Magewire\Support\Random;

class FlakeCompiler
{
    public function __construct(
        private DomElementParser $domElementParser
    ) {
        //
    }

    public function compile(string $value): string
    {
        return $this->parseTags($value, 0, 50)[0];
    }

    protected function parseTags(string $value, int $iterations, int $maxIterations): array
    {
        if ($iterations >= $maxIterations) {
            return [$value, false];
        }

        $result = '';
        $pos = 0;
        $length = strlen($value);

        while ($pos < $length) {
            // Try to match self-closing tag: <magewire:component ... />.
            if (preg_match('/<flake:([a-zA-Z0-9\-_.]+)((?:[^>\/]|\/(?!>))*)\s*\/\s*>/', $value, $match, 0, $pos)) {
                $tagStart = strpos($value, $match[0], $pos);

                // Check if there's an opening tag before this position.
                $nextOpeningMatch = preg_match(
                    '/<flake:([a-zA-Z0-9\-_.]+)((?:[^>\/]|\/(?!>))*)\s*>/',
                    $value,
                    $openingMatch,
                    0,
                    $pos
                );
                $nextOpeningPos = $nextOpeningMatch ? strpos($value, $openingMatch[0], $pos) : PHP_INT_MAX;

                // If self-closing tag comes first, process it.
                if ($tagStart <= $nextOpeningPos) {
                    $component = $match[1];
                    $attributes = $match[2];

                    // Append content before the tag
                    $result .= substr($value, $pos, $tagStart - $pos);

                    // Compile the self-closing tag.
                    $compiled = $this->compileFlake([
                        $match[0], // Full self-closing tag.
                        $component,
                        $attributes,
                        false
                    ]);

                    $result .= $compiled;
                    $pos = $tagStart + strlen($match[0]);
                    continue;
                }
            }

            // Try to match opening tag: <magewire:component ...>.
            if (preg_match('/<flake:([a-zA-Z0-9\-_.]+)((?:[^>\/]|\/(?!>))*)\s*>/', $value, $match, 0, $pos)) {
                $tagStart = strpos($value, $match[0], $pos);
                $component = $match[1];
                $attributes = $match[2];
                $openingTagEnd = $tagStart + strlen($match[0]);

                // Find the corresponding closing tag.
                $closingTagPattern = '/<\/flake:' . preg_quote($component) . '\s*>/';

                if (preg_match($closingTagPattern, $value, $closingMatch, 0, $openingTagEnd)) {
                    $closingTagStart = strpos($value, $closingMatch[0], $openingTagEnd);
                    $content = substr($value, $openingTagEnd, $closingTagStart - $openingTagEnd);
                    $closingTagEnd = $closingTagStart + strlen($closingMatch[0]);

                    // Append content before the tag.
                    $result .= substr($value, $pos, $tagStart - $pos);
                    // Recursively parse nested magewire tags in content.
                    [$content, $changes] = $this->parseTags($content, $iterations + 1, $maxIterations);
                    // Compile the opening/closing tag with content.
                    $fullTag = substr($value, $tagStart, $closingTagEnd - $tagStart);

                    $compiled = $this->compileFlake([
                        $fullTag, // Full tag with content
                        $component,
                        $attributes,
                        empty($content) ? false : $content // Actual content between tags
                    ]);

                    $result .= $compiled;
                    $pos = $closingTagEnd;
                    continue;
                }
            }

            // No more magewire tags found, append the rest
            $result .= substr($value, $pos);
            break;
        }

        return [$result, $result !== $value];
    }

    protected function compileFlake(array $matches): string
    {
        $component = $matches[1];
        $attributes = trim($matches[2]);
        $content = $matches[3];

        $parse = $this->domElementParser->newInstance()

            ->parse($attributes)

            ->attributes()

            // Set a random unique component id when none is provided.
            ->default('magewire:id', Random::string(10))
            // Map the mw:id as the mw:name when it doesn't exist.
            ->default('magewire:name', ':magewire:id')
            // Use the component name as the name alias.
            ->default('magewire:alias', $component)

            // 1. Take care of all data groups (e.g., mount, prop)
            ->each(function (DataArray $array, $value, $key) {
                if ($to = $this->renameToMagewireAttributeMeta($key)) {
                    $array->rename($key, $to);
                }
            })

            ->each(function (DataArray $array, $value, $key) {
                $subject = 'data';

                if ($key === $subject) {
                    foreach ($array->get($subject) as $name => $value) {
                        $map[$subject][substr($name, strlen(':'))] = $value;
                    }

                    // Try and replace the subject value when already exists.
                    $array->put($subject, $map[$subject] ?? []);
                }
            });

        // Fetch and transform DOM attributes.
        $attributes = $parse->fetch(fn ($value, $key) => str_starts_with($key, 'attr:'));

        $arguments = [
            'flake' => $component,

            // Accept everything as data, except those who start with attr:.
            'data' => $parse->fetch(function ($value, $key) {
                return ! str_starts_with($key, 'attr:');
            }),

            'metadata' => [
                'attributes' => array_combine(
                    array_map(fn ($key) => substr($key, 5), array_keys($attributes)),
                    $attributes
                )
            ]
        ];

        // Transform <x-{component} into a uniform @-directive.
        return '@flake(arguments: ' . json_encode($arguments) . ')';
    }

    private function renameToMagewireAttributeMeta(string $attribute): string|null
    {
        $parts = explode(':', $attribute);

        // @todo Hardcoded for the time being until more specifics gets added.
        $map = [
            'name' => [
                'prefix' => 'magewire:',
                'expects' => 1
            ],
            'prop' => [
                'prefix' => 'magewire.',
                'expects' => 2
            ],
            'mount' => [
                'prefix' => 'magewire:mount:',
                'expects' => 2
            ]
        ];

        // Magewire group indicator.
        $group = $parts[0];
        // The number of parts it expects per attribute.
        $expects = $map[$group]['expects'] ?? null;

        if (is_int($expects) && is_array($parts) && count($parts) === $expects) {
            // What the new value should be prefixed with when found.
            $prefix = $map[$group]['prefix'];

            if (in_array($group, array_keys($map), true)) {
                return $prefix . $parts[$expects - 1];
            }
        }

        return null;
    }
}
