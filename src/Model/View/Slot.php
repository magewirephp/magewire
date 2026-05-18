<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Magewirephp\Magewire\Model\View\Fragment\Component;
use Stringable;
use Traversable;

/**
 * Represents a named slot within a template fragment.
 *
 * Slots act as placeholders for content that can be filled during template rendering.
 * Each slot is associated with an component and can store content along with properties
 * and attributes from its parent component.
 *
 * @todo Needs to become lockable after the content has been updated with the final output buffer/content.
 *       This should maybe be done with a generic WithLockable trait sitting in the Magewire/Support namespace.
 *       Read-only for the class is not sufficient, since the content has to be updated.
 *
 * @mago-expect lint:too-many-methods
 */
class Slot implements Stringable, IteratorAggregate, Countable
{
    /**
     * Ordered list of entries written to this slot. Each `push()` adds a new
     * entry; `append()` extends the latest entry. `__toString()` returns the
     * latest entry only — so a re-assigned slot echoes the most recent value
     * — while iteration yields every entry in source order so a developer
     * can `foreach ($slot as $entry)` over previous assignments.
     *
     * @var array<int, string>
     */
    private array $content = [];

    public function __construct(
        private readonly string $name,
        private readonly Component $component
    ) {
    }

    /**
     * Get the slot's unique identifier name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Extend the latest entry with additional content.
     *
     * Used by `Component::echo()` to accumulate sibling child renders within
     * the parent area's default slot — multiple unbound children inside the
     * same body concatenate in source order into a single logical entry
     * rather than producing one entry per child.
     *
     * If the slot has no entries yet, the appended content seeds the first
     * entry.
     */
    public function append(string|Slot $content): static
    {
        if ($content instanceof Slot) {
            $content = $content->__toString();
        }

        if (strlen($content) === 0) {
            return $this;
        }

        if ($this->content === []) {
            $this->content[] = $content;
            return $this;
        }

        $last = array_key_last($this->content);
        $this->content[$last] .= $content;

        return $this;
    }

    /**
     * Push a new entry onto the slot's history.
     *
     * Each `<slot:name>...</slot:name>` re-assignment calls this so previous
     * values stay accessible via iteration (`foreach ($slot as $entry)`).
     * `__toString()` continues to return only the latest entry, matching
     * the natural "last write wins" expectation when echoing a slot.
     */
    public function push(string|Slot $content): static
    {
        if ($content instanceof Slot) {
            $content = $content->__toString();
        }

        $this->content[] = $content;

        return $this;
    }

    /**
     * Replace the slot's history with a single entry.
     *
     * Wipes any previously stored entries and seeds a fresh history of one.
     * Use sparingly — most callers should prefer `push()` (new entry) or
     * `append()` (extend latest) so the iteration history stays intact.
     */
    public function replace(string $content): static
    {
        $this->content = [$content];

        return $this;
    }

    public function empty(): static
    {
        $this->content = [];

        return $this;
    }

    /**
     * Retrieve a property value from the slot's component.
     */
    public function prop(string $name, mixed $default = null)
    {
        return $this->component->props()->get($name, $default);
    }

    /**
     * Retrieve an HTML attribute value from the slot's component.
     *
     * Attributes are standard HTML attributes (class, id, data-*, etc.)
     * associated with the component that owns this slot.
     */
    public function attr(string $name, mixed $default = '')
    {
        return $this->component->attrs()->get($name, $default);
    }

    /**
     * Get the component that owns this slot.
     *
     * Provides access to the parent fragment component for retrieving additional
     * context, data, or performing component-level operations.
     */
    public function component(): Component
    {
        return $this->component;
    }

    public function isEmpty(): bool
    {
        return $this->content === [];
    }

    /**
     * Get every entry that's been pushed/appended to this slot, in source
     * order. Returns an empty array when the slot has no content.
     *
     * @return array<int, string>
     */
    public function all(): array
    {
        return $this->content;
    }

    /**
     * Number of entries currently held by the slot. Useful for templates
     * that need to special-case "single value" vs "multiple values" without
     * triggering iteration.
     */
    public function count(): int
    {
        return count($this->content);
    }

    /**
     * Determines whether the slot contains multiple content items and should be iterated over.
     */
    public function iterable(): bool
    {
        return $this->count() > 1;
    }

    /**
     * Iterate every entry in source order. Lets templates foreach over the
     * slot to render each prior assignment as its own item — echoing the
     * slot directly still returns only the latest entry via __toString().
     *
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->content);
    }

    /**
     * Returns the slot's most recent entry. "Last write wins" for the common
     * echo pattern — re-assigning the slot via a second `<slot:test>` block
     * displays the new value while still preserving the previous one for
     * iteration via getIterator().
     */
    public function __toString(): string
    {
        if ($this->content === []) {
            return '';
        }

        return $this->content[array_key_last($this->content)];
    }
}
