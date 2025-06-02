<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire;

use Traversable;
trait Transparency
{
    public $target;
    function __toString()
    {
        return (string) $this->target;
    }
    function offsetExists(mixed $offset): bool
    {
        return isset($this->target[$offset]);
    }
    function offsetGet(mixed $offset): mixed
    {
        return $this->target[$offset];
    }
    function offsetSet(mixed $offset, mixed $value): void
    {
        $this->target[$offset] = $value;
    }
    function offsetUnset(mixed $offset): void
    {
        unset($this->target[$offset]);
    }
    function getIterator(): Traversable
    {
        return (function () {
            foreach ($this->target as $key => $value) {
                yield $key => $value;
            }
        })();
    }
}