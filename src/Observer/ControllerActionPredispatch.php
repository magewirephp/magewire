<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magewirephp\Magewire\MagewireServiceProvider;

class ControllerActionPredispatch implements ObserverInterface
{
    public function __construct(
        private readonly MagewireServiceProvider $magewireServiceProvider,
        private readonly RequestInterface $request
    ) {
    }

    public function execute(Observer $observer): void
    {
        $this->magewireServiceProvider->setup();
    }
}
