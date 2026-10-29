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

use Magento\Framework\Reflection\MethodsMap;
class Wrapped
{
    protected $fallback;
    public function __construct(public $target, private readonly MethodsMap $methodsMap)
    {
        //
    }
    function withFallback($fallback)
    {
        $this->fallback = $fallback;
        return $this;
    }
    function __call($method, $params)
    {
        if (!method_exists($this->target, $method)) {
            return value($this->fallback);
        }
        try {
            // A variadic parameter takes whatever else the caller passed, e.g. an event's payload in a
            // #[On] listener declared as on(...$payload), so pass every argument on as it came.
            if ($this->hasVariadicParameter($method)) {
                return $this->target->{$method}(...$params);
            }
            $arguments = $this->methodsMap->getMethodParams($this->target::class, $method);
            if (count($params) !== 0 && isset($params[0])) {
                $names = array_column($arguments, 'name');
                $count = min(count($names), count($params));
                $params = array_combine(array_slice($names, 0, $count), array_slice(array_values($params), 0, $count));
            }
            // Remove parameters that are not required by the method.
            $params = array_intersect_key($params, array_flip(array_column($arguments, 'name')));
            return $this->target->{$method}(...$params);
        } catch (\Throwable $e) {
            $shouldPropagate = true;
            $stopPropagation = function () use (&$shouldPropagate) {
                $shouldPropagate = false;
            };
            trigger('exception', $this->target, $e, $stopPropagation);
            $shouldPropagate && throw $e;
        }
    }
    private function hasVariadicParameter(string $method): bool
    {
        $parameters = (new \ReflectionMethod($this->target, $method))->getParameters();
        $last = end($parameters);
        return $last !== false && $last->isVariadic();
    }
}