<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use LogicException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\Fragment\Component;
use Magewirephp\Magewire\Model\View\Fragment\Component\Unknown;
use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\Support\Random;

class FragmentComponentFactory
{
    public function __construct(
        private array $components = []
    ) {

    }

    /**
     * Alias for creating slot component.
     *
     * `$target` is the slot name and must be passed as the element's `variant`
     * — that is what `Slot::start()` reads when registering the slot in the
     * SlotsRegistry. The id is a fresh random per slot instance and is purely
     * a uniqueness handle (not the slot name).
     */
    public function slot(string $target, AbstractBlock $block): \Magewirephp\Magewire\Model\View\Fragment\Slot
    {
        return $this->create(\Magewirephp\Magewire\Model\View\Fragment\Slot::class, [
            'id' => Random::alphabetical(10),
            'variant' => $target,
            'block' => $block
        ]);
    }

    /**
     * @template T of Component
     * @param class-string<T> $type
     * @return T
     * @throws LogicException
     */
    public function component(string $type, AbstractBlock $block, string $id, string $variant = 'default'): Component
    {
        return $this->create($this->components[$type] ?? Unknown::class, ['id' => $id, 'variant' => $variant, 'block' => $block]);
    }

    /**
     * @template T of Fragment
     * @param class-string<T> $type
     * @return T
     * @throws LogicException
     */
    private function create(string $type, array $arguments = []): Component
    {
        $fragment = Factory::create($type, $arguments);

        if ($fragment instanceof Component) {
            return $fragment;
        }

        throw new LogicException(sprintf('Class "%s" does not extend Component. Expected Component, got %s.', $type, get_debug_type($fragment)));
    }
}
