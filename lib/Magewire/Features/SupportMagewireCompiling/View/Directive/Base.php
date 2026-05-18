<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\FunctionDirective;

class Base extends FunctionDirective
{
    public function translate(string $expression): string
    {
        return "<?php echo \\Magewirephp\\Magewire\\magewire_translate({$expression}) ?>";
    }

    public function child(string $expression): string
    {
        return "<?php echo \$block->getChildHtml({$expression}) ?>";
    }
}
