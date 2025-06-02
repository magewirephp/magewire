<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportAttributes;

trait HandlesAttributes
{
    protected AttributeCollection $attributes;
    function getAttributes()
    {
        return $this->attributes ??= AttributeCollection::fromComponent($this);
    }
    function setPropertyAttribute($property, $attribute)
    {
        $attribute->__boot($this, AttributeLevel::PROPERTY, $property);
        $this->mergeOutsideAttributes(new AttributeCollection([$attribute]));
    }
    function mergeOutsideAttributes(AttributeCollection $attributes)
    {
        $this->attributes = $this->getAttributes()->concat($attributes);
    }
}