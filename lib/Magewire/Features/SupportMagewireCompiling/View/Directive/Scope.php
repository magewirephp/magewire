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

class Scope extends ScopeDirective
{
    public function start(string $expression, string $directive): string
    {
        if (method_exists($this, $directive)) {
            return $this->$directive($expression);
        }

        // By default, we assume that the statement can be started with "if",
        // similar to how it ends with "endif".
        return "<?php if ($expression): ?>";
    }

    public function end(string $directive): string
    {
        if (method_exists($this, $directive)) {
            return $this->$directive();
        }

        // By default, we assume that the statement can be closed with "endif",
        // similar to how it starts with "if".
        return "<?php endif ?>";
    }

    protected function else(string $expression): string
    {
        return "<?php else: ?>";
    }

    protected function elseif(string $expression): string
    {
        return "<?php elseif ($expression): ?>";
    }

    protected function foreach(string $expression): string
    {
        return "<?php foreach ($expression): ?>";
    }

    protected function endforeach(): string
    {
        return "<?php endforeach ?>";
    }
}
