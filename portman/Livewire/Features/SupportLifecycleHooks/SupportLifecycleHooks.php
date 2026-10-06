<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportLifecycleHooks;

class SupportLifecycleHooks extends \Livewire\Features\SupportLifecycleHooks\SupportLifecycleHooks
{
    public static function isProtectedMethod($component, string $methodName): bool
    {
        $protectedMethods = [
            'boot', 'booted', 'mount', 'exception', 'rendering', 'rendered', 'placeholder',
            'hydrate*', 'dehydrate*', 'updating*', 'updated*'
        ];

        if (\Magewirephp\Magewire\str($methodName)->is($protectedMethods)) {
            return true;
        }

        foreach (class_uses_recursive($component) as $trait) {
            $suffix = class_basename($trait);

            foreach (['boot', 'initialize', 'mount', 'hydrate', 'updating', 'updated',
                'rendering', 'rendered', 'dehydrate', 'exception', 'call', 'booted'] as $hook
            ) {
                if ($methodName === $hook . $suffix) {
                    return true;
                }
            }
        }

        return false;
    }

    public function call($methodName, $params, $returnEarly)
    {
        throw_if(
            static::isProtectedMethod($this->component, $methodName),
            new DirectlyCallingLifecycleHooksNotAllowedException($methodName, $this->component->getName())
        );

        $this->callTraitHook('call', [
            'methodName' => $methodName,
            'params' => $params,
            'returnEarly' => $returnEarly
        ]);
    }
}
