<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Action;

use Magento\Customer\Model\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewAction as ViewAction;

class Auth extends ViewAction
{
    public function __construct(
        private readonly HttpContext $httpContext
    ) {
        //
    }

    public function isCustomer(): bool
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH);
    }

    public function isGuest(): bool
    {
        return ! $this->isCustomer();
    }
}
