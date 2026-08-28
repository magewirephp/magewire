<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Exceptions;

use Magewirephp\Magewire\Support\Enum\MessageType;
use RuntimeException;

/**
 * Base type for every rejection raised while filtering an incoming Magewire update request.
 *
 * The exception is the single source of truth for how a rejection surfaces: it carries the message
 * shown to the customer, the HTTP status the response answers with, and how much that message
 * weighs. Nothing about a rejection is rendered into the page up front.
 *
 * Bind concrete subclasses to a response handler through the exception handler pool. The shipped
 * RequestFilterExceptionHandler already translates any subclass into all three.
 *
 * Note that not every subclass is necessarily thrown from a request filter. Rate limiting, for
 * example, also enforces on a per-component basis during reconstruction, which happens after the
 * filter pipeline has already passed.
 *
 * @see \Magewirephp\Magewire\Mechanisms\HandleRequests\Filter\RequestFilterInterface
 * @see \Magewirephp\Magewire\Mechanisms\HandleRequests\Filter\RequestFilterExceptionHandler
 *
 * @api
 */
abstract class RequestFilterException extends RuntimeException
{
    /**
     * Marks a response as carrying a message meant for the customer, and how much that message
     * weighs.
     *
     * Presence of this header is what makes a failed response presentable at all. Without it the
     * frontend leaves the response to Magewire's regular failure handling, so an error page or a
     * stack trace is never shown to a customer.
     *
     * Deliberately named after the message rather than after whatever renders it. How a rejection
     * surfaces is a frontend concern that can change to a modal, an inline banner or anything else
     * without the wire format following along.
     *
     * Named after the message rather than after the exception behind it for the same reason it is
     * set so rarely: an unhandled fault is an exception too, and must never mark itself presentable.
     * The header describes a body written for the customer, which is the narrower thing.
     *
     * Prefixed the way Livewire prefixes its own transport headers (X-Livewire, X-Livewire-Navigate,
     * X-Livewire-Stream), so the two stay recognisably one family. RFC 6648 discourages the "X-"
     * prefix, which is a deliberate trade against staying close to Livewire.
     *
     * The value is a structured field token as defined by RFC 8941, which keeps the header count
     * down: anything this needs to carry later belongs in a parameter on this same header rather
     * than in a header of its own.
     */
    public const MESSAGE_SEVERITY_HEADER = 'X-Magewire-Message-Severity';

    /**
     * HTTP status the response answers with.
     */
    abstract public function status(): int;

    /**
     * How much the message weighs, leaving it to the frontend to decide what renders it.
     *
     * For a rejection that realistically means error, warning or info; success is reachable but
     * has no sensible meaning here.
     */
    public function severity(): MessageType
    {
        return MessageType::WARNING;
    }

    /**
     * Additional response headers carried by this rejection.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [];
    }
}
