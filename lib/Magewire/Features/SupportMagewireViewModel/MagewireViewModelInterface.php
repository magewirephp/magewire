<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

interface MagewireViewModelInterface extends ArgumentInterface
{
    public function utils(string|null $name = null, array $arguments = []);
}
