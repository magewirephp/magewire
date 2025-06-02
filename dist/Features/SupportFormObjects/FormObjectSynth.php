<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportFormObjects;

use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Synthesizers\Synth;
use Magewirephp\Magewire\Features\SupportAttributes\AttributeCollection;
use function Magewirephp\Magewire\wrap;
class FormObjectSynth extends Synth
{
    public static $key = 'form';
    static function match($target)
    {
        return $target instanceof Form;
    }
    function dehydrate($target, $dehydrateChild)
    {
        $data = $target->toArray();
        foreach ($data as $key => $child) {
            $data[$key] = $dehydrateChild($key, $child);
        }
        return [$data, ['class' => get_class($target)]];
    }
    function hydrate($data, $meta, $hydrateChild)
    {
        $form = new $meta['class']($this->context->component, $this->path);
        $callBootMethod = static::bootFormObject($this->context->component, $form, $this->path);
        foreach ($data as $key => $child) {
            if ($child === null && Utils::propertyIsTypedAndUninitialized($form, $key)) {
                continue;
            }
            $form->{$key} = $hydrateChild($key, $child);
        }
        $callBootMethod();
        return $form;
    }
    function set(&$target, $key, $value)
    {
        if ($value === null && Utils::propertyIsTyped($target, $key) && !Utils::getProperty($target, $key)->getType()->allowsNull()) {
            unset($target->{$key});
        } else {
            $target->{$key} = $value;
        }
    }
    public static function bootFormObject($component, $form, $path)
    {
        $component->mergeOutsideAttributes(AttributeCollection::fromComponent($component, $form, $path . '.'));
        return function () use ($form) {
            wrap($form)->boot();
        };
    }
}