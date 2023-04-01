<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Magewirephp\Magewire\Model\RequestInterface;

class Dynamic implements ResolverInterface
{
    public function complies(BlockInterface $block): bool
    {
        // TODO: Implement construct() method.
    }

    public function construct(Template $block): Component
    {
        // TODO: Implement construct() method.
    }

    public function reconstruct(RequestInterface $request): Component
    {
        // TODO: Implement reconstruct() method.
    }

    public function getPublicName(): string
    {
        return 'dynamic';
    }
}
