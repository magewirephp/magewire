<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewModel;

use Magewirephp\Magewire\Support\Factory;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

trait HandlesMagewireViewModel
{
    protected MagewireViewModel|null $magewireViewModel = null;

    /**
     * Returns the view model for the current Magewire instance.
     *
     * During the deprecation period, the legacy view model is returned — extended by the
     * feature-driven implementation to maintain backwards compatibility. Once the legacy
     * version is removed, only the feature-driven model will be returned.
     *
     * @see \Magewirephp\Magewire\Features\SupportMagewireViewModel\MagewireViewModel
     */
    public function magewireViewModel(): MagewireViewModel
    {
        return $this->magewireViewModel ??= Factory::get(MagewireViewModel::class);
    }
}
