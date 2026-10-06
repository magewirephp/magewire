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
        while (is_subclass_of($reflection->getName(), \Magento\Framework\Interception\InterceptorInterface::class)
            && $reflection->getParentClass()
        ) {
            $reflection = $reflection->getParentClass();
        }

        $frameworkTraitMethods = static::frameworkTraitMethodLocations();

        $methods = array_filter($reflection->getMethods(), function ($method) use ($frameworkTraitMethods) {
            $isInFrameworkComponentClass = in_array($method->getDeclaringClass()->getName(), [
                \Magewirephp\Magewire\Component::class,
                \Magewirephp\Magewire\Component\Form::class
            ], true);
            $location = $method->getFileName() . ':' . $method->getStartLine();

            return $method->isPublic()
                && ! $method->isStatic()
                && ! $isInFrameworkComponentClass
                && ! isset($frameworkTraitMethods[$location])
                && ! str_starts_with($method->getName(), '__');
        });

        return array_map(function ($method) {
            return $method->getName();
        }, $methods);
    }

    private static function frameworkTraitMethodLocations(): array
    {
        static $locations = null;

        if ($locations !== null) {
            return $locations;
        }

        $locations = [];
        // Re-imported trait methods are declared by the importing class, but retain their source location.
        $collect = function (\ReflectionClass $class) use (&$collect, &$locations): void {
            foreach ($class->getTraits() as $trait) {
                foreach ($trait->getMethods() as $method) {
                    $locations[$method->getFileName() . ':' . $method->getStartLine()] = true;
                }

                $collect($trait);
            }
        };

        $collect(new \ReflectionClass(\Magewirephp\Magewire\Component::class));
        $collect(new \ReflectionClass(\Magewirephp\Magewire\Component\Form::class));

        return $locations;
    }
}
