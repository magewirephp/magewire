<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

use Magento\Framework\App\Request\Http;

/**
 * Request scoped view on a single incoming Magewire update request.
 *
 * Built once the request envelope has been parsed and validated, but before any component is
 * reconstructed. Where a ComponentRequestContext describes one component within the payload,
 * this describes the HTTP request carrying them.
 *
 * @see ComponentRequestContext
 *
 * @api
 */
class RequestContext
{
    private RequestAttributes|null $attributes = null;

    /**
     * @param ComponentRequestContext[] $components
     */
    public function __construct(
        private readonly Http $request,
        private readonly RequestFingerprint $requestFingerprint,
        private readonly RequestAttributesFactory $requestAttributesFactory,
        private readonly array $components = [],
        #[\SensitiveParameter]
        private readonly string|null $token = null
    ) {
    }

    public function getRequest(): Http
    {
        return $this->request;
    }

    /**
     * Every component taking part in this request, in payload order.
     *
     * @return ComponentRequestContext[]
     */
    public function getComponents(): array
    {
        return array_filter($this->components, static fn ($component) => $component instanceof ComponentRequestContext);
    }

    /**
     * Form key accompanying the request, already verified by the CSRF validator.
     */
    public function getToken(): string|null
    {
        return $this->token;
    }

    /**
     * Opaque identifier for the origin of this request.
     */
    public function getFingerprint(): string
    {
        return $this->requestFingerprint->resolve();
    }

    public function attributes(): RequestAttributes
    {
        return $this->attributes ??= $this->requestAttributesFactory->create();
    }
}
