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
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveChain;

class Auth extends Scope
{
    #[ScopeDirectiveChain(methods: ['endauth'])]
    public function auth(): string
    {
        return "<?php if(\$__magewire->action('magento.auth')->execute('is_customer')): ?>";
    }

    public function endauth(): string
    {
        return parent::endif();
    }

    #[ScopeDirectiveChain(methods: ['endguest'])]
    public function guest(): string
    {
        return "<?php if(\$__magewire->action('magento.auth')->execute('is_guest')): ?>";
    }

    public function endguest(): string
    {
        return parent::endif();
    }
}
