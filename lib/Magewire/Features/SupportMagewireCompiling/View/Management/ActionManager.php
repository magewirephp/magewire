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
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\ActionManagerFactory;

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
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $method))));

        return $this->action->$method(...$arguments);
    }

    public function load(string $namespace): ActionManager
    {
        if (class_exists($namespace)) {
            return $this->actionManagerFactory->create([
                'action' => $namespace
            ]);
        } else if (array_key_exists($namespace, $this->namespaces)) {
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
