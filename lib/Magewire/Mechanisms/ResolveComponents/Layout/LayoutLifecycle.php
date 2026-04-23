<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;

/**
 * Tracks the Magento block render tree and maps Magewire components onto it.
 *
 * Every block that enters toHtml() is pushed onto a stack, building a route — a
 * DIRECTORY_SEPARATOR-delimited path from the layout root to the current block
 * (e.g. "checkout.root/checkout.main/checkout.shipping"). When toHtml() finishes
 * the block is popped. This mirrors Magento's depth-first recursive rendering.
 *
 * Named blocks use their nameInLayout as their route segment. Anonymous blocks
 * (no nameInLayout) and duplicate-named siblings receive a sequential numeric
 * index under their parent, drawn from a shared counter per parent route.
 *
 * Magewire components are bound to a route via bind(), linking the component to
 * the block that is currently on top of the stack. All relationship queries
 * (parent, children, ancestors, closest) walk the route hierarchy to resolve
 * component-to-component relationships through the block tree.
 */
class LayoutLifecycle
{
    /** @var string[] Render stack: routes in push order, popped on completion. */
    private array $stack = [];

    /** @var array<string, AbstractBlock> All blocks seen during render: route → block. */
    private array $blocks = [];

    /** @var array<string, Component> Bound components: route → component. */
    private array $components = [];

    /** @var array<string, int> Anonymous block counters per parent route. */
    private array $anonymousCounters = [];

    /**
     * Push a block onto the render stack.
     *
     * Builds a route for the block based on its nameInLayout and the current
     * stack depth. Anonymous blocks or duplicate-named siblings are assigned
     * a numeric index from a per-parent counter to guarantee route uniqueness.
     */
    public function push(AbstractBlock $block): static
    {
        $parent = end($this->stack) ?: '';
        $name = $block->getNameInLayout();

        if ($name === null || $name === '') {
            $counterKey = $parent !== '' ? $parent : '__root';
            $this->anonymousCounters[$counterKey] ??= 0;
            $segment = (string) $this->anonymousCounters[$counterKey]++;
        } else {
            $segment = $name;
        }

        $route = $parent !== '' ? $parent . DIRECTORY_SEPARATOR . $segment : $segment;

        if (isset($this->blocks[$route])) {
            $counterKey = $parent !== '' ? $parent : '__root';
            $this->anonymousCounters[$counterKey] ??= 0;
            $route = $parent !== '' ? $parent . DIRECTORY_SEPARATOR . $this->anonymousCounters[$counterKey]++ : (string) $this->anonymousCounters[$counterKey]++;
        }

        $this->stack[] = $route;
        $this->blocks[$route] = $block;

        $component = $block->getData('magewire');

        if ($component instanceof Component) {
            $this->components[$route] = $component;
        }

        return $this;
    }

    /**
     * Pop the current block off the render stack.
     *
     * Reverts the active route to the parent, effectively closing the current
     * block's rendering scope. The stack is strictly LIFO — no identification
     * of the block is needed.
     */
    public function pop(): static
    {
        if ($this->stack !== []) {
            $route = array_pop($this->stack);

            if (! isset($this->components[$route])) {
                unset($this->blocks[$route]);
            }

            unset($this->anonymousCounters[$route]);
        }

        return $this;
    }

    /**
     * Bind a Magewire component to the block currently on top of the stack.
     *
     * Called during component construction/reconstruction to associate a
     * Component instance with its block's route. This link enables all
     * subsequent relationship queries (parentComponent, closestComponent, etc.).
     */
    public function bind(Component $component): static
    {
        $route = end($this->stack);

        if ($route !== false) {
            $this->components[$route] = $component;
        }

        return $this;
    }

    /**
     * Get the component bound to a specific block, or null if the block has no component.
     */
    public function componentFor(AbstractBlock $block): ?Component
    {
        $route = $this->routeForBlock($block);

        return $route !== null ? ($this->components[$route] ?? null) : null;
    }

    /**
     * Get the block that a component is bound to, or null if the component is not registered.
     */
    public function blockFor(Component $component): ?AbstractBlock
    {
        $route = $this->routeForComponent($component);

        return $route !== null ? ($this->blocks[$route] ?? null) : null;
    }

    /**
     * Find the nearest ancestor component above a block in the render tree.
     *
     * Walks upward from the block's parent route (skipping the block itself)
     * and returns the first component found. Used by SupportMagewireNestingComponents
     * to inject the owning component into child template dictionaries.
     */
    public function closestComponent(AbstractBlock $block): ?Component
    {
        $route = $this->routeForBlock($block);

        if ($route === null) {
            return null;
        }

        $route = $this->parentRoute($route);

        while ($route !== '') {
            if (isset($this->components[$route])) {
                return $this->components[$route];
            }

            $route = $this->parentRoute($route);
        }

        return null;
    }

    /**
     * Find the nearest ancestor component of a given component.
     *
     * Walks upward from the component's route through the block tree and
     * returns the first other component encountered. Returns null if the
     * component is at the root or has no component ancestors.
     */
    public function parentComponent(Component $component): ?Component
    {
        $route = $this->routeForComponent($component);

        if ($route === null) {
            return null;
        }

        $route = $this->parentRoute($route);

        while ($route !== '') {
            if (isset($this->components[$route])) {
                return $this->components[$route];
            }

            $route = $this->parentRoute($route);
        }

        return null;
    }

    /**
     * Collect all ancestor components above a given component, ordered nearest-first.
     *
     * Walks the full route upward, collecting every component found along the
     * way. An optional filter callback can narrow the result set.
     */
    public function componentAncestors(Component $component, ?callable $filter = null): array
    {
        $route = $this->routeForComponent($component);

        if ($route === null) {
            return [];
        }

        $ancestors = [];
        $route = $this->parentRoute($route);

        while ($route !== '') {
            if (isset($this->components[$route])) {
                $ancestors[] = $this->components[$route];
            }

            $route = $this->parentRoute($route);
        }

        return $filter ? array_filter($ancestors, $filter) : $ancestors;
    }

    /**
     * Collect all descendant components below a given component.
     *
     * Finds every component whose route starts with this component's route
     * prefix, meaning they are nested somewhere within its block subtree.
     * Includes all depths (children, grandchildren, etc.). An optional
     * filter callback can narrow the result set.
     */
    public function componentChildren(Component $component, ?callable $filter = null): array
    {
        $route = $this->routeForComponent($component);

        if ($route === null) {
            return [];
        }

        $prefix = $route . DIRECTORY_SEPARATOR;

        $children = array_values(array_filter(
            $this->components,
            static fn (Component $c, string $r) => str_starts_with($r, $prefix),
            ARRAY_FILTER_USE_BOTH
        ));

        return $filter ? array_filter($children, $filter) : $children;
    }

    /**
     * Check whether the current rendering context is inside a block with the given name.
     *
     * Inspects the segments of the active route to determine if a block with
     * this nameInLayout is an ancestor of (or is) the block currently being rendered.
     */
    public function within(string $name): bool
    {
        $current = end($this->stack);

        if ($current === false) {
            return false;
        }

        return in_array($name, explode(DIRECTORY_SEPARATOR, $current), true);
    }

    /**
     * Whether any Magewire components have been bound during this render cycle.
     */
    public function hasComponents(): bool
    {
        return $this->components !== [];
    }

    /**
     * Get the full route of the block currently being rendered.
     *
     * Returns an empty string when the stack is empty (no block is rendering).
     */
    public function route(): string
    {
        return end($this->stack) ?: '';
    }

    private function routeForBlock(AbstractBlock $block): ?string
    {
        $route = array_search($block, $this->blocks, true);

        return $route !== false ? $route : null;
    }

    private function routeForComponent(Component $component): ?string
    {
        $route = array_search($component, $this->components, true);

        return $route !== false ? $route : null;
    }

    private function parentRoute(string $route): string
    {
        $pos = strrpos($route, DIRECTORY_SEPARATOR);

        return $pos !== false ? substr($route, 0, $pos) : '';
    }
}
