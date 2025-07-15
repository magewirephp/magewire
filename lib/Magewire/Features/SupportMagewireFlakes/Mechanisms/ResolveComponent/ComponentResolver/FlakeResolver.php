<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\Mechanisms\ResolveComponent\ComponentResolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolver\LayoutResolver;

class FlakeResolver extends LayoutResolver
{
    public const FLAKES_HANDLE = 'magewire_flakes';

    // No constructor used, manually setting the accessor.
    protected string $accessor = 'flake';

    public function complies(mixed $block, mixed $magewire = null): bool
    {
        $this->conditions()->if(fn () => $block instanceof AbstractBlock);
        /** @var AbstractBlock $block */
        $this->conditions()->if(fn () => in_array(static::FLAKES_HANDLE, $block->getLayout()->getUpdate()->getHandles()));

        return $this->conditions()->evaluate($block, $magewire);
    }

    /**
     * @throws LocalizedException
     */
    public function make(string $name): bool|AbstractBlock
    {
        $layout = $this->layoutBuilder->withHandle('magewire_flakes')->build();

        return $layout->getBlock($name);
    }
}
