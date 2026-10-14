<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;

use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\store;

class SupportMagewireBackwardsCompatibility extends ComponentHook
{
    public function provide(): void
    {
        /*
         * v1's reset(array $properties, bool $boot) ran boot() on the fresh instance before taking the
         * values from it, so properties reset to what boot() assigns. booted() ran afterwards.
         * Only for components with backwards compatibility enabled; native components keep v3's reset().
         */
        on('magewire:component:reset', static function ($component, $freshInstance, $properties, $arguments) {
            if (store($component)->get('magewire:bc') !== true) {
                return null;
            }
            if (count($arguments) !== 2 || ! is_array($arguments[0]) || $arguments[1] !== true) {
                return null;
            }
            if (method_exists($freshInstance, 'boot')) {
                $freshInstance->boot();
            }

            return static function () use ($freshInstance): void {
                if (method_exists($freshInstance, 'booted')) {
                    $freshInstance->booted();
                }
            };
        });
    }

    public function hydrate(array $memo): void
    {
        $this->component
            ->getRequest()
            ->setServerMemo([
                'data' => $this->getProperties()
            ]);
    }

    public function dehydrate(ComponentContext $context): void
    {
        $bc = [
            'map' => $this->resolvePathsMap(),
            'preferences' => $this->resolvePreferences()
        ];

        foreach ($bc as $ikey => $value) {
            $context->pushEffect('bc', $value, $ikey);
        }
    }

    private function resolvePathsMap(): array
    {
        return [
            'data' => 'path:$wire',
            '__livewire' => 'path:queuedUpdates'
        ];
    }

    private function resolvePreferences(): array
    {
        return [];
    }
}
