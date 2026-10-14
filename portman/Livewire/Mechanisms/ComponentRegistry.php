<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms;

use Magewirephp\Magewire\Component;

class ComponentRegistry extends \Livewire\Mechanisms\ComponentRegistry
{
    /**
     * Magewire names a component after its layout block (or the name it was given), not after its class.
     *
     * An event target such as dispatch('event')->to('example.panel'), or v1's
     * emitTo('example.panel', 'event'), already is that name, and the browser
     * looks the component up by it. A component class, and a name registered through component(), are
     * resolved the Livewire way, so an alias still targets the name new() gives its component.
     */
    function getName($nameOrClassOrComponent)
    {
        if ($nameOrClassOrComponent instanceof Component && $nameOrClassOrComponent->getName() !== null) {
            return $nameOrClassOrComponent->getName();
        }
        if (is_string($nameOrClassOrComponent)
            && ! array_key_exists($nameOrClassOrComponent, $this->aliases)
            && ! is_subclass_of($nameOrClassOrComponent, Component::class)
        ) {
            return $nameOrClassOrComponent;
        }

        [$class, $name] = $this->getNameAndClass($nameOrClassOrComponent);

        return $name;
    }

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
