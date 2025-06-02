<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Exceptions;

class RootTagMissingFromViewException extends \Exception
{
    public function __construct()
    {
        parent::__construct(
            "Magewire encountered a missing root tag when trying to render a " .
            "component. \n When rendering a view, make sure it contains a root HTML tag."
        );
    }
}
