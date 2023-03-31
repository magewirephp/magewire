<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Magewirephp\Magewire\Model\Component\WidgetCollection;
use Magewirephp\Magewire\Model\ComponentFactory;
use Magewirephp\Magewire\Model\RequestInterface;

class Widget implements ResolverInterface
{
    protected ComponentFactory $componentFactory;

    private array $metadata = [];

    public function __construct(
        ComponentFactory $componentFactory,
        WidgetCollection $widgetCollection,
        LayoutInterface $layout
    ) {
        $this->componentFactory = $componentFactory;
        $this->widgetCollection = $widgetCollection;
        $this->layout = $layout;
    }

    public function complies(BlockInterface $block): bool
    {
        return $block instanceof \Magento\Widget\Block\BlockInterface;
    }

    public function construct(BlockInterface $block): Component
    {
        $this->metadata = $block->getData();
        $component = $this->widgetCollection->get($block->getMagewire());

        $component->name = $block->getNameInLayout();
        $component->id = $component->id ?? $component->name;

        $component->setParent($block);

        return $this->componentFactory->create($component);
    }

    public function reconstruct(RequestInterface $request): Component
    {
        $metadata = $request->getServerMemo('dataMeta');

        return $this->construct(
            $this->layout->createBlock($metadata['type'], null, ['data' => $request['serverMemo']['dataMeta']])
        );
    }

    public function getPublicName(): string
    {
        return 'widget';
    }

    public function getMetaData(): ?array
    {
        return $this->metadata;
    }
}
