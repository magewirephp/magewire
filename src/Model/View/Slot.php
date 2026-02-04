<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magewirephp\Magewire\Model\View\Fragment\Element;
use Stringable;

/**
 * Represents a named slot within a template fragment.
 *
 * Slots act as placeholders for content that can be filled during template rendering.
 * Each slot is associated with an element and can store content along with properties
 * and attributes from its parent element.
 *
 * @todo Needs to become lockable after the content has been updated with the final output buffer/content.
 *       This should maybe be done with a generic WithLockable trait sitting in the Magewire/Support namespace.
 *       Read-only for the class is not sufficient, since the content has to be updated.
 *
 * @deprecated Work in progress, do not use in production.
 */
class Slot implements Stringable
{
    private string|null $content = null;

    public function __construct(
        private readonly string $name,
        private readonly Element $element
    ) {
        //
    }

    /**
     * Get the slot's unique identifier name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Update the slot's content.
     *
     * Sets the rendered content for this slot. Once set, this content will be
     * returned when the slot is cast to a string.
     */
    public function update(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Retrieve a property value from the slot's element.
     *
     * Properties are custom data attributes associated with the element
     * that owns this slot.
     */
    public function prop(string $name, mixed $default = null)
    {
        return $this->element->data()->properties()->get($name, $default);
    }

    /**
     * Retrieve an HTML attribute value from the slot's element.
     *
     * Attributes are standard HTML attributes (class, id, data-*, etc.)
     * associated with the element that owns this slot.
     */
    public function attr(string $name, mixed $default = '')
    {
        return $this->element->data()->attributes()->get($name, $default);
    }

    /**
     * Get the element that owns this slot.
     *
     * Provides access to the parent fragment element for retrieving additional
     * context, data, or performing element-level operations.
     */
    public function element(): Element
    {
        return $this->element;
    }

    /**
     * Convert the slot to its string representation.
     */
    public function __toString(): string
    {
        return $this->content ??= sprintf('<!-- __MAGEWIRE_SLOT_%s__ -->', $this->name);
    }
}
