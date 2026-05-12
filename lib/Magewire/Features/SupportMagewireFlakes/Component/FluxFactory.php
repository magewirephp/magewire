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
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Support\Factory;

class FluxFactory extends FlakeFactory
{
    protected string|array $handles = 'magewire_flux';

    public function create(array $arguments = []): Component
    {
        return Factory::create(Flux::class, $arguments);
    }

    /**
     * Create and returns a new instance of a named Flux block bound with
     * a Magewire component and its resolver.
     */
    public function createByName(string $name, array $data = []): AbstractBlock|false
    {
        $block = $this->layout()->getBlock($name);

        if ($block instanceof AbstractBlock) {
            $data['magewire'] ??= $block->getData('magewire') ?? $this->create();
            $data['magewire:resolver'] ??= 'flux';
            $data['magewire:alias'] ??= $name;

            $block->addData($data);
        }

        return $block;
    }
}
