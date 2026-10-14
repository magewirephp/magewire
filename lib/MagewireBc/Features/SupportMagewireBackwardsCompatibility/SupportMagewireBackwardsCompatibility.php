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
         * v1's skipRender(false) took back an earlier skipRender() call, where Livewire's skipRender(false)
         * skips rendering like skipRender(). Only for components with backwards compatibility enabled.
         */
        on('magewire:component:skip-render', static function ($component, $html) {
            if ($html !== false || store($component)->get('magewire:bc') !== true) {
                return null;
            }

            return static function () use ($component): void {
                store($component)->unset('skipRender');
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
