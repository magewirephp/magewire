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
use Magewirephp\Magewire\Support\Distributor;
use Magewirephp\Magewire\Support\Factory;
use Psr\Log\LoggerInterface;

abstract class Element extends Fragment
{
    private Distributor|null $data = null;
    private DataArray|null $dictionary = null;

    public function __construct(
        protected string $variant,
        private AbstractBlock $block,
        protected SlotsRegistry $slotsRegistry,
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($logger, $escaper, $modifiers);
    }

    public function dictionary(): DataArray
    {
        return $this->dictionary ??= Factory::create(DataArray::class);
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

    public function data(): DomElementData
    {
        return $this->data ??= Factory::create(DomElementData::class);
    }

    /**
     * Returns the elements parent block.
     */
    public function parent(): AbstractBlock
    {
        return $this->block;
    }

    public function track(): static
    {
        $this->slotsRegistry->track($this);
        return $this;
    }

    public function untrack(): static
    {
        $this->slotsRegistry->untrack();
        return $this;
    }
}
