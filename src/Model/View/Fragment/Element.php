<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\DomElementData;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\Distributor;
use Magewirephp\Magewire\Support\Factory;
use Psr\Log\LoggerInterface;

/**
 * @deprecated Work in progress, do not use in production.
 */
abstract class Element extends Fragment
{
    private Distributor|null $data = null;
    private DataArray|null $dictionary = null;

    public function __construct(
        protected string $variant,
        private AbstractBlock $block,
        private SlotsRegistry $slotsRegistry,
        LoggerInterface $logger,
        Escaper $escaper,
        string $id,
        array $modifiers = []
    ) {
        parent::__construct($logger, $escaper, $modifiers, $id);
    }

    public function dictionary(): DataCollection
    {
        return $this->dictionary ??= Factory::create(DataCollection::class);
    }

    /**
     * @return DataArray
     */
    public function attributes(): DataArray
    {
        return $this->data()->attributes();
    }

    /**
     * Returns the element properties/settings.
     */
    public function properties(): DataArray
    {
        return $this->data()->properties();
    }

    public function prop(string $name, mixed $default = null)
    {
        return $this->properties()->get($name, $default);
    }

    public function data(): DomElementData
    {
        return $this->data ??= Factory::create(DomElementData::class);
    }

    /**
     * Returns the elements parent block.
     */
    public function block(): AbstractBlock
    {
        return $this->block;
    }

    public function track(): static
    {
        $this->slots()->track($this);
        return $this;
    }

    public function untrack(): static
    {
        $this->slots()->untrack();
        return $this;
    }

    /**
     * Bubble the finalized render output up the area stack.
     *
     * If this element is nested inside another element, its render does NOT
     * hit the surrounding output buffer — it appends to the parent area's
     * `default` slot instead. The parent then reads that slot when its own
     * template renders, producing nested HTML naturally.
     *
     * The same rule applies as the parent itself finishes: if it too is
     * nested, its render bubbles up another level. Eventually the chain
     * reaches an element with no enclosing area — that's the top-level
     * render, which echoes to Magento's surrounding output buffer.
     *
     * This sidesteps PHP ob_start nesting entirely for inter-element flow:
     * children never write to the buffer, so buffer-level mismatches caused
     * by nested template engines cannot drop or reorder output.
     */
    protected function echo(string $output): void
    {
        $parent = $this->slots()->parent();
        // Append the current output onto the area's default slot.
        $this->slots()->default()->append($output);

        if ($parent) {
            // Append the current area's default slot content onto the parent area default slot.
//            $parent->append(
//                $this->slots()->default()
//            );
//            echo $this->variant;
//            return;
        }

        echo $output;
    }

    protected function slots(): SlotsRegistry
    {
        return $this->slotsRegistry;
    }
}
