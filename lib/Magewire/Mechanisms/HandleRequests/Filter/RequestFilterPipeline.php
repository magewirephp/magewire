<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests\Filter;

use InvalidArgumentException;
use Magewirephp\Magewire\Exceptions\RequestFilterException;
use Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext;

/**
 * Runs every registered request filter, in registration order, against a single update request.
 *
 * Filters are registered per area through the "filters" argument, ordered by their sortOrder. The
 * first filter to throw ends the request; no later filter runs and no component is reconstructed.
 *
 * @see RequestFilterInterface
 *
 * @api
 */
final class RequestFilterPipeline
{
    /**
     * @param RequestFilterInterface[] $filters
     */
    public function __construct(
        private readonly array $filters = []
    ) {
        foreach ($this->filters as $name => $filter) {
            if (! $filter instanceof RequestFilterInterface) {
                throw new InvalidArgumentException(
                    sprintf('Request filter "%s" must implement %s.', $name, RequestFilterInterface::class)
                );
            }
        }
    }

    /**
     * @throws RequestFilterException When any filter rejects the request.
     */
    public function check(RequestContext $context): void
    {
        foreach ($this->filters as $filter) {
            $filter->check($context);
        }
    }

    /**
     * @return RequestFilterInterface[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
