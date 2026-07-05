<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\FunctionDirective;

class Base extends FunctionDirective
{
    public function translate(string $expression): string
    {
        return "<?php echo __({$expression}) ?>";
    }

    public function child(string $expression): string
    {
        return "<?php echo (\$block && \$block->getChildBlock({$expression})) ? \$block->getChildBlock({$expression})->toHtml() : '' ?>";
    }
}
