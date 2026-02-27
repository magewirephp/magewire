<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Model\Request as MagewireRequest;
use Magewirephp\Magewire\Model\RequestInterface;

/**
 * @deprecated TBD
 */
trait Request
{
    /**
     * @deprecated TBD
     */
    public function getRequest(): RequestInterface
    {
        $request = ObjectManager::getInstance()->get(MagewireRequest::class);

        return $request;
    }
}
