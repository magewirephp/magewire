<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magewirephp\Magewire\Drawer\Utils;
use Magento\Framework\App\ObjectManager;
use function Magewirephp\Magewire\on;

class ComponentHookRegistry extends \Livewire\ComponentHookRegistry
{
    static function getComponents()
    {
        return self::$components;
    }

    static function register($hook)
    {
        if (method_exists($hook, 'provide')) {
            $hook->provide();
        }

        if (in_array($hook, static::$componentHooks)) {
            return;
        }

        static::$componentHooks[] = $hook;
    }

    static function boot()
    {
        static::$components = new \WeakMap();

        foreach (static::$componentHooks as $hook) {
            on('mount', function ($component, $params, $key, $parent) use ($hook) {
                if (! $hook = static::initializeHook($hook, $component)) {
                    return;
                }

                $hook->callBoot();
                $hook->callMount($params, $parent);
            });

            on('hydrate', function ($component, $memo) use ($hook) {
                if (! $hook = static::initializeHook($hook, $component)) {
                    return;
                }

                $hook->callBoot();
                $hook->callHydrate($memo);
            });
        }

        on('update', function ($component, $fullPath, $newValue) {
            $propertyName = \Magewirephp\Magewire\Drawer\Utils::beforeFirstDot($fullPath);

            return static::proxyCallToHooks($component, 'callUpdate')($propertyName, $fullPath, $newValue);
        });

        on('call', function ($component, $method, $params, $addEffect, $earlyReturn) {
            return static::proxyCallToHooks($component, 'callCall')($method, $params, $earlyReturn);
        });

        on('render', function ($component, $view, $data) {
            return static::proxyCallToHooks($component, 'callRender')($view, $data);
        });

        on('rendered', function ($component, $view, $data) {
            return static::proxyCallToHooks($component, 'callRendered')($view, $data);
        });

        on('dehydrate', function ($component, $context) {
            static::proxyCallToHooks($component, 'callDehydrate')($context);
        });

        on('destroy', function ($component, $context) {
            static::proxyCallToHooks($component, 'callDestroy')($context);
        });

        on('exception', function ($target, $e, $stopPropagation) {
            if ($target instanceof \Magewirephp\Magewire\Component) {
                static::proxyCallToHooks($target, 'callException')($e, $stopPropagation);
            }
        });
    }

    static public function initializeHook($hook, $target)
    {
        if (! isset(static::$components[$target])) {
            static::$components[$target] = [];
        }

        $hook = ObjectManager::getInstance()->create($hook::class);

        $hook->setComponent($target);

        // If no `skip` method has been implemented, then boot the hook anyway
        if (method_exists($hook, 'skip') && $hook->skip()) {
            return;
        }

        static::$components[$target][] = $hook;

        return $hook;
    }
}
