<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Fragment;

use Magewirephp\Magewire\Model\View\Fragment\Html;

class FlakeFragment extends Html
{
    protected array $attributes = ['wire:flake'];
}
