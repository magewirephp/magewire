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
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Layout\LayoutLifecycle;

trait HandlesMagentoLayout
{
    private AbstractBlock|null $magewireBlock = null;
    private ComponentResolver|null $magewireResolver = null;
    private LayoutLifecycle|null $magewireLayoutLifecycle = null;

    public function magewireResolver(ComponentResolver|null $resolver = null): ComponentResolver|null
    {
        if ($resolver) {
            $this->magewireResolver = $resolver;
        }

        return $this->magewireResolver;
    }

    public function magewireBlock(AbstractBlock|null $block = null): AbstractBlock|null
    {
        if ($block) {
            $this->magewireBlock = $block;
        }

        return $this->magewireBlock;
    }

    public function magewireLayoutLifecycle(LayoutLifecycle|null $lifecycle = null): LayoutLifecycle|null
    {
        if ($lifecycle) {
            $this->magewireLayoutLifecycle = $lifecycle;
        }

        return $this->magewireLayoutLifecycle;
    }

    /**
     * @deprecated has been replaced with block()
     * @see static::magewireBlock()
     */
    public function setParent(Template|null $parent): static
    {
        $this->magewireBlock($parent);

        return $this;
    }

    /**
     * @deprecated has been replaced with block()
     * @see static::magewireBlock()
     */
    public function getParent(): AbstractBlock|null
    {
        return $this->magewireBlock();
    }

    /**
     * @deprecated has been replaced with magewireBlock()
     * @see static::magewireBlock()
     */
    public function block(AbstractBlock|null $block = null): AbstractBlock|null
    {
        return $this->magewireBlock($block);
    }

    /**
     * @deprecated has been replaced with magewireResolver()
     * @see static::magewireResolver()
     */
    public function resolver(AbstractBlock|null $block = null): AbstractBlock|null
    {
        return $this->magewireBlock($block);
    }
}
