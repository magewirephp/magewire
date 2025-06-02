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

use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
abstract class Synth
{
    function __construct(public ComponentContext $context, public $path)
    {
    }
    public static function getKey()
    {
        throw_unless(property_exists(static::class, 'key'), new \Exception('You need to define static $key property on: ' . static::class));
        return static::$key;
    }
    abstract static function match($target);
    static function matchByType($type)
    {
        return false;
    }
    function get(&$target, $key)
    {
        if (is_array($target)) {
            return $target[$key] ?? null;
        }
        return $target->{$key};
    }
    function __call($method, $params)
    {
        if ($method === 'dehydrate') {
            throw new \Exception('You must define a "dehydrate" method');
        }
        if ($method === 'hydrate') {
            throw new \Exception('You must define a "hydrate" method');
        }
        if ($method === 'hydrateFromType') {
            throw new \Exception('You must define a "hydrateFromType" method');
        }
        if ($method === 'get') {
            throw new \Exception('This synth doesn\'t support getting properties: ' . get_class($this));
        }
        if ($method === 'set') {
            throw new \Exception('This synth doesn\'t support setting properties: ' . get_class($this));
        }
        if ($method === 'unset') {
            throw new \Exception('This synth doesn\'t support unsetting properties: ' . get_class($this));
        }
        if ($method === 'call') {
            throw new \Exception('This synth doesn\'t support calling methods: ' . get_class($this));
        }
    }
}