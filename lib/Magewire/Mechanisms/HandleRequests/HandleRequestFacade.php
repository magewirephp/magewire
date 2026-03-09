<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Mechanisms\HandleRequests\HandleRequests as HandleRequestsMechanism;

class HandleRequestFacade
{
    public function __construct(
        private readonly HandleRequestsMechanism $mechanism
    ) {
        
    }

    public function mechanism(): HandleRequestsMechanism
    {
        return $this->mechanism;
    }

    /**
     * @throws NoSuchEntityException
     * @throws ComponentNotFoundException
     */
    public function update()
    {
        return $this->mechanism->handleUpdate();
    }

    public function getUpdateUri(): string
    {
        return $this->mechanism->getUpdateUri();
    }
}
