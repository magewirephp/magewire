<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Exception;

use Exception;

class RootTagMissingFromViewException extends MagewireException
{
    /**
     * @param Exception|null $cause
     * @param int $code
     */
    public function __construct(Exception $cause = null, $code = 0)
    {
        parent::__construct(__('Missing root tag when trying to render the Magewire component'), $cause, $code);
    }
}
