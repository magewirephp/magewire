<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;
use function Magewirephp\Magewire\on;

class SupportMagewireBackwardsCompatibility extends ComponentHook
{
    public function provide()
    {
        on('magewire:component:reconstruct', function (ComponentRequestContext $componentRequestContext) {
            return function (AbstractBlock $block) use ($componentRequestContext) {
                $component = $block->getData('magewire');

                if ($component instanceof Component) {
                    $snapshot = $componentRequestContext->getSnapshot();

                    $component->getRequest()->setServerMemo([
                        'data' => $snapshot->getData()
                    ]);
                }
            };
        });
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
