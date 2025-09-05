<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment\Modifier;

use Magento\Framework\App\State as ApplicationState;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\FragmentModifier;

class Developer extends FragmentModifier
{
    public function __construct(
        private readonly ApplicationState $applicationState
    ) {
        //
    }

    public function modify(Fragment $fragment): Fragment
    {
        if ($fragment instanceof Fragment\Html && $this->applicationState->getMode() === ApplicationState::MODE_DEVELOPER) {
            $fragment->withAttribute('magewire-fragment');
        }

        return $fragment;
    }
}
