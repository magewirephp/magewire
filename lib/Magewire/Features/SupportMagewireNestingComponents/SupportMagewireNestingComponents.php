<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireNestingComponents;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutLifecycleManager;
use function Magewirephp\Magewire\on;

class SupportMagewireNestingComponents extends ComponentHook
{
    public function __construct(
        private readonly LayoutLifecycleManager $renderLifecycleManager
    ) {
    }

    public function provide(): void
    {
        on('magento:template:render', function () {
            return function (array $result) {
                $magewire  = $result['dictionary']['magewire'] ?? false;
                $component = $result['component'] ?? false;

                if ($magewire && $component) {
                    return $result;
                }

                $closest = $this->renderLifecycleManager->target('magewire')->closestComponent($result['block']);

                if ($closest) {
                    $result['dictionary']['magewire'] = $closest;
                }

                return $result;
            };
        });

        on('magewire:component:construct', function () {
            // Returns a callable that will execute after the component is constructed.
            return function (AbstractBlock $block) {
                $component = $block->getData('magewire');

                if ($component instanceof Component) {
                    $this->renderLifecycleManager->target('magewire')->bind($component);
                }

                return $block;
            };
        });

        on('magewire:component:reconstruct', function () {
            // Returns a callable that will execute after the component is reconstructed.
            return function (AbstractBlock $block) {
                $component = $block->getData('magewire');

                if ($component instanceof Component) {
                    $this->renderLifecycleManager->target('magewire')->bind($component);
                }

                return $block;
            };
        });
    }
}
