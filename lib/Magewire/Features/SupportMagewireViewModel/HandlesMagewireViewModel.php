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

    public function magewireViewModel(): MagewireViewModel
    {
        return $this->magewireViewModel ??= Factory::get(MagewireViewModel::class);
    }
}
