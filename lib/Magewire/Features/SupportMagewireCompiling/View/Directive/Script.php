<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirective;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveChain;

class Script extends ScopeDirective
{
    #[ScopeDirectiveChain(['endscript'])]
    public function script(): string
    {
        return "<?php \${$this->var('fragment')} = \$__magewire->utils()->fragment()->make()->script()->start() ?>";
    }

    public function endscript(): string
    {
        return "<?php \${$this->var('fragment')}->end() ?>";
    }
}
