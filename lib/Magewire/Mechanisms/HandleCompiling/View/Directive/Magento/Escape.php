<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\FunctionDirective;

class Escape extends FunctionDirective
{
    public function url(string $expression): string
    {
        return "<?php echo \$escaper->escapeUrl({$expression}) ?>";
    }
}
