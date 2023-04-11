<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component\Resolver;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Widget\Model\Widget as WidgetModel;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\MissingComponentException;
use Magewirephp\Magewire\Model\Component\ResolverInterface;
use Magewirephp\Magewire\Model\ComponentFactory;
use Magewirephp\Magewire\Model\RequestInterface;

class Widget implements ResolverInterface
{
    protected ComponentFactory $componentFactory;
    protected WidgetModel $widget;
    private LayoutInterface $layout;

    public function __construct(
        ComponentFactory $componentFactory,
        WidgetModel $widget,
        LayoutInterface $layout
    ) {
        $this->componentFactory = $componentFactory;
        $this->widget = $widget;
        $this->layout = $layout;
    }

    public function getName(): string
    {
        return 'widget';
    }

    public function complies(BlockInterface $block): bool
    {
        return $block instanceof \Magento\Widget\Block\BlockInterface;
    }

    public function construct(AbstractBlock $block): Component
    {
        $component = $this->widgetCollection->get($block->getMagewire());

        $component->name = $block->getNameInLayout();
        $component->id = $component->id ?? $component->name;

        $component->setParent($block);
        $component->setMetaData($block->getData());

        return $component;
    }

    /**
     * @throws MissingComponentException
     */
    public function reconstruct(RequestInterface $request): Component
    {
        $metadata = $request->getServerMemo('dataMeta');
        $name = $metadata['magewire'];

        $widget = $this->resolveWidget($name);

        if (! $widget) {
            throw new MissingComponentException(__('Magewire widget not found'));
        }
        if (! $this->widgetCollection->has($name)) {
            throw new MissingComponentException(__('Magewire component not found'));
        }

        /** @var Template $block */
        $block = $this->layout->createBlock($widget['@']['type'], null, ['data' => $metadata]);

        return $this->construct($block);
    }

    public function resolveWidget(string $name): ?array
    {
        $widget = array_values(array_filter($this->widget->getWidgets(), static function ($widget) use ($name) {
            return isset($widget['parameters']['magewire']) && $widget['parameters']['magewire']['value'] === $name;
        }));

        return $widget[0] ?? null;
    }
}
