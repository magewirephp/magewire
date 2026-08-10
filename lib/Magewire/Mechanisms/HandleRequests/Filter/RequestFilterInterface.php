<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests\Filter;

use Magewirephp\Magewire\Exceptions\RequestFilterException;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;

/**
 * A single check applied to an incoming update request before any component is reconstructed.
 *
 * Filters run once per HTTP request, in the order they are registered. A filter either lets the
 * request continue by returning, or rejects it by throwing a RequestFilterException subclass.
 * The exception carries both the status and the message the rejection answers with, so nothing
 * about it has to be published into the page up front.
 *
 * Implementations must be inexpensive relative to the work they protect, and must not reconstruct,
 * hydrate or render anything.
 *
 * @see RequestFilterPipeline
 * @see \Magewirephp\Magewire\Exceptions\RequestFilterException
 *
 * @api
 */
interface RequestFilterInterface
{
    /**
     * @throws RequestFilterException When the request must not continue.
     */
    public function check(RequestContext $context): void;
}
