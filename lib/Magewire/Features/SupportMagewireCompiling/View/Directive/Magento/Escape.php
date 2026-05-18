<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\FunctionDirective;

class Escape extends FunctionDirective
{
    public function url(string $expression): string
    {
        return "<?php echo \$escaper->escapeUrl({$expression}) ?>";
    }
}
