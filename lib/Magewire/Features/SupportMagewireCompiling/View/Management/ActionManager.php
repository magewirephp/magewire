<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management;

use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewAction;

class ActionManager
{
    /**
     * @param array<string, ViewAction|string> $namespaces
     */
    public function __construct(
        private readonly ViewAction $action,
        private readonly ActionManagerFactory $actionManagerFactory,
        private readonly array $namespaces = []
    ) {
        //
    }

    public function execute(string $method, ...$arguments): mixed
    {
        $method = lcfirst(str_replace('_', '', ucwords($method, '_')));

        return $this->action->$method(...$arguments);
    }

    /**
     * Load and create an ActionManager instance based on the provided namespace.
     *
     * This method attempts to resolve the namespace in the following order:
     * 1. If the namespace is an existing class, creates an ActionManager with that class
     * 2. If the namespace exists in the registered namespaces array:
     *    - Uses the object directly if it's already instantiated
     *    - Creates the object via ObjectManager if it's a class name
     * 3. Returns the current instance if no resolution is possible
     *
     * @param string $namespace The namespace/class name to load, or a key from registered namespaces
     * @return ActionManager Returns a new ActionManager instance or the current instance as fallback
     */
    public function load(string $namespace): ActionManager
    {
        if (class_exists($namespace)) {
            return $this->actionManagerFactory->create([
                'action' => $namespace
            ]);
        } elseif (array_key_exists($namespace, $this->namespaces)) {
            if (is_object($this->namespaces[$namespace])) {
                return $this->actionManagerFactory->create([
                    'action' => $this->namespaces[$namespace]
                ]);
            }

            return $this->actionManagerFactory->create([
                'action' => ObjectManager::getInstance()->create($this->namespaces[$namespace])
            ]);
        }

        return $this;
    }
}
