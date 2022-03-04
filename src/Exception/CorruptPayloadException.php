<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Exception;

use Exception;

class CorruptPayloadException extends MagewireException
{
    /**
     * CorruptPayloadException constructor.
     * @param string $name
     * @param Exception|null $cause
     * @param int $code
     */
    public function __construct(string $name, Exception $cause = null, $code = 0)
    {
        parent::__construct(
            __('Magewire encountered corrupt data when trying to hydrate the %1 component. Ensure that the [name, id, data] of the Magewire component wasn\'t tampered with between requests.', [$name]),
            $cause,
            $code
        );
    }
}
