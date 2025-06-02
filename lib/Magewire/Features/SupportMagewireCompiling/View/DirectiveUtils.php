<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\App\State as ApplicationState;

/**
 * Provides utility methods for writing directives, reducing repetitive boilerplate code.
 *
 * This class contains a curated set of helper methods that simplify common directive-related tasks.
 * The collection will expand over time based on evolving needs.
 */
class DirectiveUtils
{
    public function __construct(
        private readonly ApplicationState $applicationState
    ) {
        //
    }

    /**
     * Returns if we're in production mode.
     */
    public function isProductionMode(): bool
    {
        return $this->applicationState->getMode() !== ApplicationState::MODE_PRODUCTION;
    }

    /**
     * Returns if we're not in production mode instead of checking a specific mode.
     */
    public function isDeveloperMode(): bool
    {
        return !! $this->isProductionMode();
    }
}
