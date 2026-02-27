<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewModel;

use InvalidArgumentException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magento\Framework\View\RenderLifecycleManager;
use Magewirephp\Magewire\ComponentHook;
use function Magewirephp\Magewire\on;

class SupportMagewireViewModel extends ComponentHook
{
    private bool $includeMagewireViewModel = false;

    public function __construct(
        private readonly MagewireViewModelInterfaceFactory $magewireViewModelFactory
    ) {
        //
    }

    public function provide(): void
    {
        on('magewire:render:start', function (RenderLifecycleManager $manager, AbstractBlock $block) {
            if ($this->isRootMagewireBlock($block)) {
                $this->includeMagewireViewModel = true;
            }

            /*
             * Ensures the Magewire view model is automatically bound to the block as a "view_model" data argument,
             * if it hasn't been set or is not an instance of the expected Magewire view model class.
             *
             * This reduces the need for manual binding, which is often repetitive since many sibling blocks depend on it.
             *
             * Why bind a view model instead of exposing a global template variable like $magewireViewModel?
             * Because when a block is moved outside its parent "magewire" wrapper, it still needs to maintain compatibility.
             * Using a shared view model ensures this compatibility without requiring changes to the template.
             *
             * Relying on global dictionary variables would force template modifications in such cases—something this method avoids.
             */
            if ($this->includeMagewireViewModel) {
                $model = $block->getData('view_model');

                if ($model && ! $model instanceof MagewireViewModelInterface) {
                    throw new InvalidArgumentException('View model must be an instance of MagewireViewModelInterface');
                } elseif ($model === null) {
                    $block->setData('view_model', $this->magewireViewModelFactory->create());
                }
            }
        });
    }

    private function isRootMagewireBlock(AbstractBlock $block): bool
    {
        return $block->getNameInLayout() === 'magewire';
    }
}
