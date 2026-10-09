<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Keeps cached Magewire 1 event registrations resolvable during Hyvä configuration generation.
 *
 * @deprecated Magewire 3 no longer requires a Hyvä Tailwind extension.
 */
class HyvaConfigGenerateBefore implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        // Magewire 3 ships its CSS directly, so the former Tailwind extension is no longer registered.
    }
}
