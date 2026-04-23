<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Support\Factory;

/**
 * @deprecated TBD
 */
trait Request
{
    /** @deprecated TBD */
    private RequestInterface|null $__magewireRequest = null;

    /**
     * @deprecated TBD
     */
    public function getRequest(RequestInterface|null $request = null): RequestInterface
    {
        if ($request) {
            $this->__magewireRequest = $request;
        }

        return $this->__magewireRequest ??= Factory::create(RequestInterface::class);
    }
}
