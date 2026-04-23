<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\Resolver;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\CallHookArgumentResolverInterface;

/**
 * Reconstructs full array properties for v1 updatingXxx and updatedXxx hooks.
 *
 * In v1, updatingData received the ENTIRE array property with the new nested
 * value already populated. In v3, updatingData receives only the individual
 * nested value and its path.
 *
 * This resolver reads the current array from the component, fills in the new
 * value at the nested path, and returns the full array as a named parameter,
 * matching v1 behavior.
 *
 * NOT registered in the default resolver pool. Modules that need this behavior
 * inject it via DI with a component and method mapping.
 */
class ArrayPropertyReconstructionResolver implements CallHookArgumentResolverInterface
{
    public function __construct(
        private readonly array $mapping = []
    ) {
    }

    public function supports(Component $component, string $method): bool
    {
        foreach ($this->mapping as $class => $methods) {
            if (is_a($component, $class) && ($methods[$method] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    public function resolve(Component $component, string $method, array $params): array
    {
        if (count($params) < 2) {
            return [$method, $params];
        }

        $property = $this->extractPropertyName($method);

        if ($property === null || ! property_exists($component, $property)) {
            return [$method, $params];
        }

        [$value, $path] = $params;

        $data = $component->{$property};

        if (is_array($data) && is_string($path)) {
            $this->fill($data, $path, $value);
        }

        return [$method, [$property => $data]];
    }

    /**
     * Extract the property name from a hook method like 'updatingData' or 'updatedAddress'.
     */
    private function extractPropertyName(string $method): string|null
    {
        if (preg_match('/^(?:updating|updated)(.+)$/', $method, $matches)) {
            return lcfirst($matches[1]);
        }

        return null;
    }

    /**
     * Set a value in a nested array using dot notation path.
     */
    private function fill(array &$array, string $path, mixed $value): void
    {
        $parts = explode('.', $path);
        $current = &$array;

        foreach ($parts as $index => $part) {
            if ($index === array_key_last($parts)) {
                $current[$part] = $value;
                continue;
            }

            if (! is_array($current[$part] ?? null)) {
                $current[$part] = [];
            }

            $current = &$current[$part];
        }
    }
}
