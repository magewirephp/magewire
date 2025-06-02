<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Magento;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Scope;

class Auth extends Scope
{
    public function auth(string $expression): string
    {
        return "<?php if(\$__magewire->action('magento.auth')->execute('is_customer')): ?>";
    }

    public function endauth(): string
    {
        return "<?php endif ?>";
    }

    public function guest(string $expression): string
    {
        return "<?php if(\$__magewire->action('magento.auth')->execute('is_guest')): ?>";
    }

    public function endguest(): string
    {
        return "<?php endif ?>";
    }
}
