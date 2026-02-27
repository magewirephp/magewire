<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewModel;

use Magewirephp\Magewire\Model\View\Utils as ViewUtils;

class MagewireViewModel implements MagewireViewModelInterface
{
    function __construct(
        private readonly ViewUtils $utils
    ) {
        //
    }

    public function utils(string|null $name = null, array $arguments = []): ViewUtils
    {
        return $name ? $this->utils->$name($arguments) : $this->utils;
    }
}
