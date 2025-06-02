<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoLayouts;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\ComponentResolver;

trait HandlesMagentoLayout
{
    protected ?AbstractBlock $block = null;
    protected ?ComponentResolver $resolver = null;

    function resolver(ComponentResolver|null $resolver = null): ComponentResolver|null
    {
        if ($resolver) {
            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    function block(AbstractBlock|null $block = null): AbstractBlock|null
    {
        if ($block) {
            $this->block = $block;
        }

        return $this->block;
    }

    /**
     * @deprecated has been replaced with block()
     * @see static::block()
     */
    function setParent(?Template $parent): static
    {
        $this->block($parent);

        return $this;
    }

    /**
     * @deprecated has been replaced with block()
     * @see static::block()
     */
    function getParent(): ?AbstractBlock
    {
        return $this->block();
    }
}
