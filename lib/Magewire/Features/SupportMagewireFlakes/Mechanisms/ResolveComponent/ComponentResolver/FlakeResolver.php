<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Mechanisms\ResolveComponent\ComponentResolver;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Component\FlakeFactory;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Snapshot;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutBlockArgumentsFactory;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutResolver;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;
use Magewirephp\Magewire\Support\Conditions;

class FlakeResolver extends LayoutResolver
{
    protected string $accessor = 'flake';

    public function __construct(
        protected readonly FlakeFactory $flakeFactory,
        Conditions $conditions,
        LayoutBlockArgumentsFactory $layoutBlockArgumentsFactory,
        LayoutManager $layoutManager
    ) {
        parent::__construct($conditions, $layoutBlockArgumentsFactory, $layoutManager);
    }

    public function complies(mixed $block, mixed $magewire = null): bool
    {
        return false;
    }

    public function construct(AbstractBlock $block): AbstractBlock
    {
        /*
         * For the situations where a Flake exists, but is by layout not bound with a Magewire arguments,
         * missing the actual object. Flakes do not always have to be a Magewire component, but when not,
         * it will become an empty Magewire Flake component to at least give it all it's powers.
         */
        if ($block->hasData('magewire:alias') && ! $block->hasData('magewire')) {
            $block->setData('magewire', $this->flakeFactory->create());
        }

        return parent::construct($block);
    }

    protected function canMemorizeLayoutHandles(): bool
    {
        return false;
    }

    protected function recoverLayoutHandles(Snapshot $snapshot): array
    {
        return ['magewire_flakes'];
    }
}
