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
    public function __construct(string $name, string $message = null, Exception $cause = null, $code = 0)
    {
        $phrase = 'Magewire security vulnerability: ' . (sprintf(($message ?? 'Magewire encountered corrupt data
            when trying to hydrate the %1 component. Ensure that the [name, id, resolver and data] of the Magewire component wasn\'t
            tampered with between requests.'), [$name]));

        parent::__construct(
            __($phrase),
            $cause,
            $code
        );
    }
}
