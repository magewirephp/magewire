<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewModel;

use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

trait HandlesMagewireViewModel
{
    protected MagewireViewModel|null $viewModel = null;

    public function viewModel()
    {
        return $this->viewModel ??= ObjectManager::getInstance()->get(MagewireViewModel::class);
    }
}
