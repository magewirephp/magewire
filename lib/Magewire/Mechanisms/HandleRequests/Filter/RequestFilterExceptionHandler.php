<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests\Filter;

use Exception;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magewirephp\Magewire\Exceptions\RequestFilterException;
use Magewirephp\Magewire\Model\App\AbstractExceptionHandler;

/**
 * Answers a filter rejection with the status, message and notification type carried by the
 * exception itself.
 *
 * The type travels as a response header rather than inside the payload, which keeps the response
 * body exactly what it was: the message. That header doubles as the marker telling the frontend
 * this body was written for the customer and may be presented.
 *
 * One handler serves every rejection, so a new filter only needs its exception bound in the
 * exception handler pool. The pool matches on the concrete exception class, which is why each
 * subclass still gets its own entry.
 */
class RequestFilterExceptionHandler extends AbstractExceptionHandler
{
    public function handle(Exception $exception, bool $subsequent = false): Exception|callable
    {
        if (! $exception instanceof RequestFilterException) {
            return parent::handle($exception, $subsequent);
        }

        return static function (HttpResponseInterface $response) use ($exception) {
            $response->setHeader(RequestFilterException::MESSAGE_SEVERITY_HEADER, $exception->severity()->type(), true);

            foreach ($exception->headers() as $name => $value) {
                $response->setHeader($name, $value, true);
            }

            $response->setBody($exception->getMessage());
            $response->setHttpResponseCode($exception->status());

            return $response;
        };
    }
}
