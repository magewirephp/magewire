<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\ViewModel\Playwright;

use Exception;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class UiPreview implements ArgumentInterface
{
    public function __construct(
        private readonly ApplicationState $applicationState
    ) {
    }

    public function applicationState(): ApplicationState
    {
        return $this->applicationState;
    }

    public function exception(): Exception
    {
        return new Exception('A representative component exception for styling and browser tests.');
    }
}
