<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Model\Component\ResolverInterface;

class Dynamic implements ResolverInterface
{
    public function getNamespace(): string
    {
        return 'dynamic';
    }

    public function complies(BlockInterface $block): bool
    {
        return false;
    }

    public function build(BlockInterface $block): BlockInterface
    {
        return $block;
    }

    public function rebuild(array $data): BlockInterface
    {
        return null;
    }
}
