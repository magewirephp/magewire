<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Component;

class ComponentFactory
{
    protected ObjectManagerInterface $objectManager;

    private array $instances = [];

    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
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
}
