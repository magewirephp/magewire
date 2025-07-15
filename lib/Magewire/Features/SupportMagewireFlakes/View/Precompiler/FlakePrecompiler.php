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
        $iterations = 0;
        $maxIterations = 50;

        do {
            $newValue = preg_replace_callback(
                '/<x-([a-zA-Z0-9\-_.]+)([^>]*?)>(.*?)<\/x-\1>/s',
                [$this, 'compileFlake'],
                $value
            );

            if ($newValue === $value || ++$iterations >= $maxIterations) {
                break;
            }

            $value = $newValue;
        } while (true);

        return $value;
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
            ->default('mw:id', uniqid())
            // Map the mw:id as the mw:name when it doesn't exist.
            ->default('mw:name', ':mw:id')
            // Use the component name as the name alias.
            ->default('mw:alias', $component)

            // Set some defaults to always be sure these are available.
            ->default('data', [])
            ->default('attributes', [])

            // Use all data binds as mount method arguments during compilation.
            ->each(function (DataArray $array, $value, $key) {
                if (str_starts_with($key, ':')) {
                    $array->rename($key, str_replace(':', 'magewire:mount:', $key));
                }
            })

            // Replace all "mw:" prefixes with "magewire:".
            ->each(function (DataArray $array, $value, $key) {
                if (str_starts_with($key, 'mw:')) {
                    $array->rename($key, str_replace('mw:', 'magewire:', $key));
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

        $arguments = [
            'content' => $content,
            'flake' => $component,

            'data' => $parse->fetch(function ($value, $key) {
                return str_starts_with($key, 'magewire:');
            })
        ];

        // Transform <x-{component} into a uniform @-directive.
        return '@flake(arguments: ' . base64_encode(serialize($arguments)) . ')';
    }
}
