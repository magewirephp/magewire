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
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\RenderLifecycleManager;
use function Magewirephp\Magewire\on;

class SupportMagewireNestingComponents extends ComponentHook
{
    public function __construct(
        private readonly RenderLifecycleManager $renderLifecycleManager
    ) {
        //
    }

    function provide(): void
    {
        on('magewire:construct', function () {
            // Returns a callable that will execute after the component is constructed.
            return function (AbstractBlock $block) {
                $this->renderLifecycleManager->push($block->getData('magewire'));

                return $block;
            };
        });

        on('magewire:reconstruct', function () {
            // Returns a callable that will execute after the component is reconstructed.
            return function (AbstractBlock $block) {
                $this->renderLifecycleManager->push($block->getData('magewire'));

                return $block;
            };
        });
    }
}
