<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents;

use Magewirephp\Magewire\Component;

/**
 * @internal Designed specifically to track the render lifecycle of Magewire components.
 */
class RenderLifecycleManager
{
    private array $index = [];
    private array $lifecycle = [];
    private array $routes = [];

    private string $route = '';

    public function push(Component $component): static
    {
        $id = $component->id();
        $route = $this->route ? $this->route . DIRECTORY_SEPARATOR . $id : $id;

        $this->index[$id] = $route;
        $this->lifecycle[$id] = $route;
        $this->routes[$route][] = $component;
        $this->route = $route;

        return $this;
    }

    public function pop(Component $component): static
    {
        $id = $component->id();

        unset($this->lifecycle[$id]);

        if (! isset($this->index[$id])) {
            return $this;
        }

        $route = $this->index[$id];
        $this->route = $this->getParentRoute($route);

        return $this;
    }

    public function isset(Component $component): bool
    {
        return isset($this->routes[$component->id()]);
    }

    public function getParent(Component $component): ?Component
    {
        $id = $component->id();

        if (! isset($this->index[$id])) {
            return null;
        }

        $route = $this->index[$id];
        $parentRoute = $this->getParentRoute($route);

        return $this->routes[$parentRoute][count($this->routes[$parentRoute]) - 1] ?? null;
    }

    public function getAncestors(Component $component, callable|null $filter = null): array
    {
        $id = $component->id();

        if (! isset($this->index[$id])) {
            return [];
        }

        $route = $this->index[$id];
        $parts = explode(DIRECTORY_SEPARATOR, $route);
        $ancestors = [];

        while (count($parts) > 1) {
            array_pop($parts);
            $parentRoute = implode(DIRECTORY_SEPARATOR, $parts);

            if (isset($this->routes[$parentRoute])) {
                $ancestors = array_merge($ancestors, $this->routes[$parentRoute]);
            }
        }

        return $filter ? array_filter($ancestors, $filter) : $ancestors;
    }

    public function getChildren(Component $component, callable|null $filter = null): array
    {
        $id = $component->id();

        if (! isset($this->index[$id])) {
            return [];
        }

        $route    = $this->index[$id] . DIRECTORY_SEPARATOR;
        $siblings = array_filter($this->routes, fn ($key) => str_starts_with($key, $route), ARRAY_FILTER_USE_KEY);
        $children = [];

        foreach ($siblings as $child) {
            $children = array_merge($children, $child);
        }

        return $filter ? array_filter($children, $filter) : $children;
    }

    public function getIndexes(): array
    {
        return $this->index;
    }

    public function getLifecycle(): array
    {
        return $this->lifecycle;
    }

    private function getParentRoute(string $route): string
    {
        $parts = explode(DIRECTORY_SEPARATOR, $route);
        array_pop($parts);

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
