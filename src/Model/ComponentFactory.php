<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\MissingComponentException;

class ComponentFactory
{
    protected ObjectManagerInterface $objectManager;
    protected Layout $layout;
    protected DynamicComponentProvider $dynamicComponentProvider;

    private array $instances = [];

    public function __construct(
        ObjectManagerInterface $objectManager,
        Layout $layout,
        DynamicComponentProvider $dynamicComponentProvider
    ) {
        $this->objectManager = $objectManager;
        $this->layout = $layout;
        $this->dynamicComponentProvider = $dynamicComponentProvider;
    }

    public function create(Component $component = null, array $data = []): Component
    {
        $class = $component ? get_class($component) : Component::class;

        if (isset($this->instances[$class])) {
            return $this->objectManager->create($class, [$data]);
        }

        $this->instances[$class] = $component;
        return $component;
    }

    public function createDynamic(string $id, string $component)
    {
        $block = $this->layout->createBlock(Template::class);

        $block->setNameInLayout($id);
        $block->setDynamicName($component);

        try {
            $block->setData('magewire', $this->dynamicComponentProvider->get($component));
        } catch (MissingComponentException $exception) {
            // So should it just return the block, or should be set and exception template here?
        }

        return $block;
    }
}
