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

class LayoutArgumentOverlay
{
    public function get(Component $component, string $name, mixed $default = null): mixed
    {
        $arguments = $this->arguments($component);

        return array_key_exists($name, $arguments) ? $arguments[$name] : $default;
    }

    /**
     * Use the layout value when present. Arrays add entries and replace keys;
     * selected keyed values can remove entries from the original array.
     */
    public function value(Component $component, string $name, mixed $original, array $removals = []): mixed
    {
        $arguments = $this->arguments($component);

        if (! array_key_exists($name, $arguments)) {
            return $original;
        }

        return $this->apply($original, $arguments[$name], $removals);
    }

    public function apply(mixed $original, mixed $overlay, array $removals = []): mixed
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

    public function removed(array $overlay, array $removals): array
    {
        return array_filter($overlay, static fn ($value) => in_array($value, $removals, true));
    }

    private function arguments(Component $component): array
    {
        return $component->magewireResolver()?->arguments()->all() ?? [];
    }
}
