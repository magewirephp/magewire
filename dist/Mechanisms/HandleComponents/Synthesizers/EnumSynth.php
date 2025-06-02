<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\HandleComponents\Synthesizers;

class EnumSynth extends Synth
{
    public static $key = 'enm';
    static function match($target)
    {
        return is_object($target) && is_subclass_of($target, 'BackedEnum');
    }
    static function matchByType($type)
    {
        return is_subclass_of($type, 'BackedEnum');
    }
    static function hydrateFromType($type, $value)
    {
        if ($value === '') {
            return null;
        }
        return $type::from($value);
    }
    function dehydrate($target)
    {
        return [$target->value, ['class' => get_class($target)]];
    }
    function hydrate($value, $meta)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $class = $meta['class'];
        return $class::from($value);
    }
}