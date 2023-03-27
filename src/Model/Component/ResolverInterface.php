<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component;

use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Component;

interface ResolverInterface
{
    /**
     * Checks for very specific data elements to see if
     * this component complies the requirements.
     */
    public function complies(BlockInterface $block): bool;

    /**
     * Build component based on type.
     */
    public function construct(BlockInterface $block): Component;

    /**
     * Re-build component based on subsequent request data.
     */
    public function reconstruct(array $data): Component;

    /**
     * Returns the unique (publicly visible) name of the resolver.
     */
    public function getPublicName(): string;

    /**
     * Returns options data meta for component (re-) construction.
     */
    public function getMetaData(): ?array;
}
