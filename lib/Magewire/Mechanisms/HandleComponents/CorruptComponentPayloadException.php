<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

use Exception;
use Magewirephp\Magewire\Exceptions\BypassViewHandler;

class CorruptComponentPayloadException extends Exception
{
    use BypassViewHandler;

    function __construct()
    {
        parent::__construct(
            "Magewire encountered corrupt data when trying to hydrate a component. \n".
            "Ensure that the [name, id, data] of the Magewire component wasn't tampered with between requests."
        );
    }
}
