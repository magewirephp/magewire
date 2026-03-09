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
use Magewirephp\Magewire\Model\View\Fragment\Element;
use Magewirephp\Magewire\Support\Factory;

class FragmentElementFactory
{
    public function __construct(
        private array $elements = []
    ) {
    }

    /**
     * Alias for creating slot elements.
     */
    public function slot(string $target, AbstractBlock $block): Element\Slot
    {
        return $this->element('slot', $block, $target);
    }

    /**
     * @template T of Element
     * @param class-string<T> $type
     * @return T
     * @throws LogicException
     */
    public function element(string $type, AbstractBlock $block, string $variant = 'default'): Element
    {
        $type = $this->elements[$type] ?? Element\Unknown::class;

        return $this->create($type, ['variant' => $variant, 'block' => $block]);
    }

    /**
     * @template T of Fragment
     * @param class-string<T> $type
     * @return T
     * @throws LogicException
     */
    private function create(string $type, array $arguments = []): Fragment
    {
        $fragment = Factory::create($type, $arguments);

        if ($fragment instanceof Fragment) {
            return $fragment;
        }

        throw new LogicException(sprintf('Class "%s" does not implement Fragment interface. Expected Fragment, got %s.', $type, get_debug_type($fragment)));
    }
}
