<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component;

use Magento\Framework\View\Element\BlockInterface;

interface ResolverInterface
{
    /**
     * Returns the unique namespace of the resolver.
     */
    public function getNamespace(): string;

    /**
     * Checks for very specific data elements to see if
     * this component complies the requirements.
     */
    public function complies(BlockInterface $block): bool;

    /**
     * Build component based on type.
     */
    public function build(BlockInterface $block): BlockInterface;

    /**
     * Re-build component based on subsequent request data.
     */
    public function rebuild(array $data): BlockInterface;
}
