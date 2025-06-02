<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler;

use Magento\Framework\App\ObjectManager;

abstract class HandlerTypeContext
{
    public function __construct(
        private readonly HandlerType $handler
    ) {
        //
    }

    public function also(): HandlerType
    {
        return $this->handler();
    }

    public function return(): HandlerType
    {
        return $this->also();
    }

    public function only(array $arguments = []): HandlerType
    {
        return ObjectManager::getInstance()->create(get_class($this->handler()), $arguments);
    }

    protected function handler(): HandlerType
    {
        return $this->handler;
    }
}
