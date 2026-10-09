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

use Magewirephp\Magewire\Drawer\Utils;
use ReflectionAttribute;
use ReflectionObject;
use Illuminate\Support\Collection;
class AttributeCollection extends Collection
{
    static function fromComponent($component, $subTarget = null, $propertyNamePrefix = '')
    {
        $instance = new static();
        $reflected = new ReflectionObject($subTarget ?? $component);
        foreach (static::getClassAttributesRecursively($reflected) as $attribute) {
            $instance->push(tap($attribute->newInstance(), function ($attribute) use ($component, $subTarget) {
                $attribute->__boot($component, AttributeLevel::ROOT, null, null, $subTarget);
            }));
        }
        // A Magento Interceptor redeclares plugged methods without their attributes, so read the methods of the class itself.
        foreach (Utils::getReflectionWithoutInterceptors($subTarget ?? $component)->getMethods() as $method) {
            foreach ($method->getAttributes(Attribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $instance->push(tap($attribute->newInstance(), function ($attribute) use ($component, $method, $propertyNamePrefix, $subTarget) {
                    $attribute->__boot($component, AttributeLevel::METHOD, $propertyNamePrefix . $method->getName(), $method->getName(), $subTarget);
                }));
            }
        }
        foreach ($reflected->getProperties() as $property) {
            foreach ($property->getAttributes(Attribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $instance->push(tap($attribute->newInstance(), function ($attribute) use ($component, $property, $propertyNamePrefix, $subTarget) {
                    $attribute->__boot($component, AttributeLevel::PROPERTY, $propertyNamePrefix . $property->getName(), $property->getName(), $subTarget);
                }));
            }
        }
        return $instance;
    }
    protected static function getClassAttributesRecursively($reflected)
    {
        $attributes = [];
        while ($reflected) {
            foreach ($reflected->getAttributes(Attribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $attributes[] = $attribute;
            }
            $reflected = $reflected->getParentClass();
        }
        return $attributes;
    }
}