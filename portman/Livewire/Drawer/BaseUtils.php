<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Drawer;

class BaseUtils extends \Livewire\Drawer\BaseUtils
{
    static function getPublicPropertiesDefinedOnSubclass($target) {
        return static::getPublicProperties($target, function ($property) {
            // Filter out any properties from the first-party Component class...
            return $property->getDeclaringClass()->getName() !== \Magewirephp\Magewire\Component::class;
        });
    }

    static function getPublicMethodsDefinedBySubClass($target)
    {
        $methods = array_filter((new \ReflectionObject($target))->getMethods(), function ($method) {
            $isInBaseComponentClass = $method->getDeclaringClass()->getName() === \Livewire\Component::class;

            return $method->isPublic()
                && ! $method->isStatic()
                && ! $isInBaseComponentClass;
        });

        return array_map(function ($method) {
            return $method->getName();
        }, $methods);
    }
}
