<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Component;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;
use Magewirephp\Magewire\Support\Factory;

class FlakeFactory
{
    protected string|array $handles = 'magewire_flakes';

    private LayoutInterface|null $layout = null;

    public function __construct(
        protected readonly LayoutManager $layoutManager
    ) {
    }

    public function create(array $arguments = []): Component
    {
        return Factory::create(Flake::class, $arguments);
    }

    /**
     * Create and returns a new instance of a named Flake block bound with
     * a Magewire component and its resolver.
     */
    public function createByName(string $name, array $data = []): AbstractBlock|false
    {
        $block = $this->layout()->getBlock($name);

        if ($block instanceof AbstractBlock) {
            $data['magewire'] ??= $this->create();
            $data['magewire:resolver'] ??= 'flake';
            $data['magewire:alias'] ??= $name;

            $block->addData($data);
        }

        return $block;
    }

    /**
     * @mago-expect lint:halstead
     */
    protected function layout(): LayoutInterface
    {
        if ($this->layout === null) {
            $this->layout = $this->layoutManager->decorator()->decorateForPagelessBlockFetching($this->layoutManager->factory()->create());

            $this->layout->getUpdate()->addHandle($this->handles);
        }

        return $this->layout;
    }
}
