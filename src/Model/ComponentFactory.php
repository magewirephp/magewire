<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;

class ComponentFactory
{
    protected ObjectManagerInterface $objectManager;
    protected array $instances = [];

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param Component|null $component
     * @param array $data
     * @return Component
     */
    public function create(Component $component = null, array $data = []): Component
    {
        $class = $component ? get_class($component) : Component::class;

        if (isset($this->instances[$class])) {
            return $this->objectManager->create($class, [$data]);
        }

        $this->instances[$class] = $component;
        return $component;
    }

    /**
     * @param AbstractBlock $block
     * @return Component
     */
    public function virtual(AbstractBlock $block): Component
    {
        $component = $this->objectManager->create(Component\Virtual::class);

        $component->id = $block->getNameInLayout();
        $component->name = $component->id;

        return $component;
    }
}
