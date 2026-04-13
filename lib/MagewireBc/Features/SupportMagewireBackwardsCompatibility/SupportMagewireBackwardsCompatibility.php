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

class SupportMagewireBackwardsCompatibility extends ComponentHook
{
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
