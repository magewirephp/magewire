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

use stdClass;
class StdClassSynth extends Synth
{
    public static $key = 'std';
    static function match($target)
    {
        return $target instanceof stdClass;
    }
    function dehydrate($target, $dehydrateChild)
    {
        $data = (array) $target;
        foreach ($target as $key => $child) {
            $data[$key] = $dehydrateChild($key, $child);
        }
        return [$data, []];
    }
    function hydrate($value, $meta, $hydrateChild)
    {
        $obj = new stdClass();
        foreach ($value as $key => $child) {
            $obj->{$key} = $hydrateChild($key, $child);
        }
        return $obj;
    }
    function set(&$target, $key, $value)
    {
        $target->{$key} = $value;
    }
}