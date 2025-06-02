<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

class Escape extends Directive
{
    public function url(string $url): string
    {
        return "<?= \$escaper->escapeUrl({$url}) ?>";
    }
}
