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

class Pipe implements \Stringable, \ArrayAccess, \IteratorAggregate
{
    use Transparency;
    function __construct($target)
    {
        $this->target = $target;
    }
    function __invoke(...$params)
    {
        if (empty($params)) {
            return $this->target;
        }
        [$before, $through, $after] = [[], null, []];
        foreach ($params as $key => $param) {
            if (!$through) {
                if (is_callable($param)) {
                    $through = $param;
                } else {
                    $before[$key] = $param;
                }
            } else {
                $after[$key] = $param;
            }
        }
        $params = [...$before, $this->target, ...$after];
        $this->target = $through(...$params);
        return $this;
    }
}