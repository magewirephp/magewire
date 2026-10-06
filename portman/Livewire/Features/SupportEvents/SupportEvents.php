<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportEvents;

use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Exceptions\EventHandlerDoesNotExist;
use Magewirephp\Magewire\Exceptions\MethodNotFoundException;
use Magewirephp\Magewire\Features\SupportAttributes\AttributeLevel;
use Magewirephp\Magewire\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Magewirephp\Magewire\Mechanisms\HandleComponents\BaseRenderless;

use function Magewirephp\Magewire\wrap;

class SupportEvents extends \Livewire\Features\SupportEvents\SupportEvents
{
    public function call($method, $params, $returnEarly)
    {
        if ($method !== '__dispatch') {
            return;
        }

        [$name, $params] = $params;
        $names = static::getListenerEventNames($this->component);

        if (! in_array($name, $names)) {
            throw new EventHandlerDoesNotExist($name);
        }

        $method = static::getListenerMethodName($this->component, $name);

        // A listener can be rewritten by a component action, so check its target too.
        if (method_exists($this->component, $method)) {
            $allowed = array_diff(Utils::getPublicMethodsDefinedBySubClass($this->component), ['render']);

            if (! in_array($method, $allowed, true)
                || SupportLifecycleHooks::isProtectedMethod($this->component, $method)
            ) {
                throw new MethodNotFoundException($method);
            }
        }

        $returnEarly(wrap($this->component)->{$method}(...$params));

        $isRenderless = $this->component->getAttributes()
            ->filter(fn ($attribute) => is_subclass_of($attribute, BaseRenderless::class))
            ->filter(fn ($attribute) => $attribute->getName() === $method)
            ->filter(fn ($attribute) => $attribute->getLevel() === AttributeLevel::METHOD)
            ->count() > 0;

        if ($isRenderless) {
            $this->component->skipRender();
        }
    }
}
