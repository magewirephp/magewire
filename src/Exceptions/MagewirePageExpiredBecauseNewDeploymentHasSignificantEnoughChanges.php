<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MagewirePageExpiredBecauseNewDeploymentHasSignificantEnoughChanges extends HttpException
{
    function __construct()
    {
        parent::__construct(
            419,
            'New deployment contains changes to Magewire that have invalidated currently open browser pages.'
        );
    }
}
