<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Precompiler;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Precompiler;
use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\Parser\DomElementParser;

class FlakePrecompiler extends Precompiler
{
    public function __construct(
        private readonly DomElementParser $domElementParser
    ) {
        //
    }

    public function precompile(string $value): string
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
            if (preg_match('/<magewire:([a-zA-Z0-9\-_.]+)((?:[^>\/]|\/(?!>))*)\s*\/\s*>/', $value, $match, 0, $pos)) {
                $tagStart = strpos($value, $match[0], $pos);
                $component = $match[1];
                $attributes = $match[2];

                // Append content before the tag
                $result .= substr($value, $pos, $tagStart - $pos);

                // Compile the self-closing tag
                $compiled = $this->compileFlake([
                    $match[0], // Full self-closing tag
                    $component,
                    $attributes,
                    '' // Empty content for self-closing tags
                ]);

                $result .= $compiled;
                $pos = $tagStart + strlen($match[0]);
            } else {
                // No more self-closing magewire tags found, append the rest
                $result .= substr($value, $pos);
                break;
            }
        }

        return [$result, $result !== $value];
    }

    protected function compileFlake(array $matches): string
    {
        $component = $matches[1];
        $attributes = trim($matches[2]);

        $parse = $this->domElementParser->newInstance()

            ->parse($attributes)

            ->attributes()

            // Set a random unique component id when none is provided.
            ->default('magewire:id', uniqid())
            // Map the mw:id as the mw:name when it doesn't exist.
            ->default('magewire:name', ':magewire:id')
            // Use the component name as the name alias.
            ->default('magewire:alias', $component)

            // 1. Take care of all data groups (e.g., mount, prop)
            ->each(function(DataArray $array, $value, $key) {
                if ($to = $this->renameToMagewireAttributeMeta($key)) {
                    $array->isset($to) ? $array->put($to, $value) : $array->rename($key, $to);
                }
            })

            ->each(function (DataArray $array, $value, $key) {
                $subject = 'data';

                if ($key === $subject) {
                    foreach ($array->get($subject) as $name => $value) {
                        $map[$subject][substr($name, strlen(':'))] = $value;
                    }

                    $array->replace($subject, $map[$subject] ?? []);
                }
            });

        // Fetch and transform DOM attributes.
        $attributes = $parse->fetch(fn($value, $key) => str_starts_with($key, 'attr:'));

        $arguments = [
            'flake' => $component,

            // Accept everything as data, except those who start with attr:.
            'data' => $parse->fetch(function ($value, $key) {
                return ! str_starts_with($key, 'attr:');
            }),

            'metadata' => [
                'attributes' => array_combine(
                    array_map(fn($key) => substr($key, 5), array_keys($attributes)), $attributes
                )
            ]
        ];

        // Transform <x-{component} into a uniform @-directive.
        return '@flake(arguments: ' . base64_encode(serialize($arguments)) . ')';
    }

    private function renameToMagewireAttributeMeta(string $attribute): string|null
    {
        $parts = explode(':', $attribute);

        // Hardcoded for the time being until more specifics gets added.
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
