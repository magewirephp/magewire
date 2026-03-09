<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magento\Framework\View\Element\AbstractBlock;

class SlotsManager
{
    private array $items = [];

    public function add(AbstractBlock $block, string $content, string $name): string
    {
        $id = $block->getNameInLayout() . '_' . $name;

        //        if (isset($this->items[$id])) {
        //            throw new AlreadyExistsException();
        //        }

        $this->items[$id] = $content;

        return $id;
    }

    public function render(string $name, AbstractBlock $block): string
    {
        $id = $block->getNameInLayout() . '_' . $name;

        return $this->items[$id] ?? 'NONO';
    }
}
