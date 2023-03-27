<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Magewirephp\Magewire\Model\ComponentFactory;

class Widget implements ResolverInterface
{
    protected ComponentFactory $componentFactory;

    public function __construct(
        ComponentFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    public function complies(BlockInterface $block): bool
    {
        return $block instanceof \Magento\Widget\Block\BlockInterface;
    }

    public function construct(BlockInterface $block): Component
    {
        return $this->componentFactory->create();
    }

    public function reconstruct(array $data): Component
    {
        return null;
    }

    public function getPublicName(): string
    {
        return 'widget';
    }

    public function getMetaData(): ?array
    {
        return null;
    }
}
