<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments;

use Magewirephp\Magewire\Component;

final class LayoutArgumentOverlay
{
    public static function get(Component $component, string $name, mixed $default = null): mixed
    {
        $resolver = $component->magewireResolver();

        if ($resolver === null) {
            return $default;
        }

        $arguments = $resolver->arguments()->all();

        return array_key_exists($name, $arguments) ? $arguments[$name] : $default;
    }

    /**
     * Use the layout value when present. Arrays add entries and replace keys;
     * selected keyed values can remove entries from the original array.
     */
    public static function value(Component $component, string $name, mixed $original, array $removals = []): mixed
    {
        $resolver = $component->magewireResolver();

        if ($resolver === null) {
            return $original;
        }

        $arguments = $resolver->arguments()->all();

        if (! array_key_exists($name, $arguments)) {
            return $original;
        }

        return self::apply($original, $arguments[$name], $removals);
    }

    public static function apply(mixed $original, mixed $overlay, array $removals = []): mixed
    {
        if (! is_array($overlay)) {
            return $overlay;
        }

        $original = is_array($original) ? $original : [];

        foreach ($overlay as $key => $value) {
            if (in_array($value, $removals, true)) {
                if (! is_int($key)) {
                    unset($original[$key]);
                }

                continue;
            }

            if (is_int($key)) {
                $original[] = $value;
                continue;
            }

            $original[$key] = $value;
        }

        return $original;
    }

    public static function removed(array $overlay, array $removals): array
    {
        return array_filter($overlay, static fn ($value) => in_array($value, $removals, true));
    }
}
