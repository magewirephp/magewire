<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Mechanisms\ResolveComponent\ComponentResolver;

use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespacePool;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Snapshot;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentArguments\LayoutBlockArgumentsFactory;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutResolver;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;
use Magewirephp\Magewire\Support\Conditions;

class FlakeResolver extends LayoutResolver
{
    protected string $accessor = 'flake';

    public function __construct(
        Conditions $conditions,
        LayoutBlockArgumentsFactory $layoutBlockArgumentsFactory,
        LayoutManager $layoutManager,
        private readonly FlakeNamespacePool $namespacePool,
        string $accessor = 'flake'
    ) {
        $this->accessor = $accessor;
        parent::__construct($conditions, $layoutBlockArgumentsFactory, $layoutManager);
    }

    public function complies(mixed $block, mixed $magewire = null): bool
    {
        return false;
    }

    protected function canMemorizeLayoutHandles(): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    protected function recoverLayoutHandles(Snapshot $snapshot): array
    {
        return $this->namespacePool->get($this->accessor)->handles();
    }
}
