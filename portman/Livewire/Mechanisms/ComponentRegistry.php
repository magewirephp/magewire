<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms;

class ComponentRegistry extends \Livewire\Mechanisms\ComponentRegistry
{
    function new($nameOrClass, $id = null)
    {
        [$class, $name] = $this->getNameAndClass($nameOrClass);

        $component = new $class;

        $component->setId($id ?: str()->random(20));

        $component->setName($name);

        // // Parameters passed in automatically set public properties by the same name...
        // foreach ($params as $key => $value) {
        //     if (! property_exists($component, $key)) continue;

        //     // Typed properties shouldn't be set back to "null". It will throw an error...
        //     if ((new \ReflectionProperty($component, $key))->getType() && is_null($value)) continue;

        //     $component->$key = $value;
        // }

        return $component;
    }
}
