<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Model\View\UtilsInterface;

class Application implements UtilsInterface
{
    function __construct(
        private readonly ApplicationState $applicationState
    ) {
        //
    }
}
