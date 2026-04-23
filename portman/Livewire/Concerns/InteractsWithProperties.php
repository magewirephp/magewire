<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Concerns;

use Magento\Framework\DataObject;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Support\Factory;

trait InteractsWithProperties
{
    function hasProperty($prop)
    {
        return property_exists($this, Utils::beforeFirstDot($prop));
    }

    function getPropertyValue($name)
    {
        $value = $this->{Utils::beforeFirstDot($name)};

        if (Utils::containsDots($name)) {
            return data_get($value, Utils::afterFirstDot($name));
        }

        return $value;
    }

    function fill($values)
    {
        $publicProperties = array_keys($this->all());

        if ($values instanceof DataObject) {
            $values = $values->toArray();
        }

        foreach ($values as $key => $value) {
            if (in_array(Utils::beforeFirstDot($key), $publicProperties)) {
                data_set($this, $key, $value);
            }
        }
    }

    public function reset(...$properties)
    {
        $properties = count($properties) && is_array($properties[0])
            ? $properties[0]
            : $properties;

        // Reset all
        if (empty($properties)) {
            $properties = array_keys($this->all());
        }

        $freshInstance = Factory::create(static::class);

        foreach ($properties as $property) {
            $property = str($property);

            // Check if the property contains a dot which means it is actually on a nested object like a FormObject
            if (str($property)->contains('.')) {
                $propertyName = $property->afterLast('.');
                $objectName = $property->before('.');

                // form object reset
//                if (is_subclass_of($this->{$objectName}, Form::class)) {
//                    $this->{$objectName}->reset($propertyName);
//                    continue;
//                }

                $object = data_get($freshInstance, $objectName, null);

                if (is_object($object)) {
                    $isInitialized = (new \ReflectionProperty($object, (string) $propertyName))->isInitialized($object);
                } else {
                    $isInitialized = false;
                }
            } else {
                $isInitialized = (new \ReflectionProperty($freshInstance, (string) $property))->isInitialized($freshInstance);
            }

            // Handle resetting properties that are not initialized by default.
            if (! $isInitialized) {
                data_forget($this, (string) $property);
                continue;
            }

            data_set($this, $property, data_get($freshInstance, $property));
        }
    }

    function all()
    {
        return Utils::getPublicPropertiesDefinedOnSubclass($this);
    }
}
