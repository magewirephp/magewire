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
        $reflection = new \ReflectionObject($target);

        // Magento interceptors redeclare public methods, including methods from the base component.
        if ($target instanceof \Magento\Framework\Interception\InterceptorInterface) {
            $reflection = $reflection->getParentClass();
        }

        $methods = array_filter($reflection->getMethods(), function ($method) {
            $isInFrameworkComponentClass = in_array($method->getDeclaringClass()->getName(), [
                \Magewirephp\Magewire\Component::class,
                \Magewirephp\Magewire\Component\Form::class
            ], true);

            return $method->isPublic()
                && ! $method->isStatic()
                && ! $isInFrameworkComponentClass
                && ! str_starts_with($method->getName(), '__');
        });

        return array_map(function ($method) {
            return $method->getName();
        }, $methods);
    }
}
