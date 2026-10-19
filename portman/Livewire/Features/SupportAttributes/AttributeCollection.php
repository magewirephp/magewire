<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportAttributes;

use Magewirephp\Magewire\Drawer\Utils;
use ReflectionAttribute;
use ReflectionObject;

class AttributeCollection extends \Livewire\Features\SupportAttributes\AttributeCollection
{
    static function fromComponent($component, $subTarget = null, $propertyNamePrefix = '')
    {
        $instance = new static;

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
}
