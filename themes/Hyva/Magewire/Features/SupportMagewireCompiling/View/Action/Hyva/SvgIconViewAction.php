<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportMagewireCompiling\View\Action\Hyva;

use Hyva\Theme\ViewModel\SvgIcons;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewAction;

class SvgIconViewAction extends ViewAction
{
    public function __construct(
        private readonly SvgIcons $icons
    ) {
        //
    }

    public function heroicon(string $icon): string
    {
        return $this->icons->renderHtml($icon);
    }
}
