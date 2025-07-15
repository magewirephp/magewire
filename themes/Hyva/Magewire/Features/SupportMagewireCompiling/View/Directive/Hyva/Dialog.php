<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportMagewireCompiling\View\Directive\Hyva;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

class Dialog extends Directive
{
    public function compile(string $expression, string $directive): string
    {
        return 'hyva-dialog';
    }
}
