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

class Flake extends Html
{
    public function start(): static
    {
        return parent::start()

            ->withModifier(function () {
                $this->withAttribute('data-magewire-flake');
            });
    }
}
