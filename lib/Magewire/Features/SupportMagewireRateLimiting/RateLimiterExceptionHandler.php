<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Exception;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magewirephp\Magewire\Exceptions\RequestFilterException;
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use Magewirephp\Magewire\Model\App\AbstractExceptionHandler;

/**
 * @deprecated Rate limit rejections are answered by the generic request filter exception handler,
 *             which takes the status and message straight off the exception.
 * @see \Magewirephp\Magewire\Mechanisms\HandleRequests\Filter\RequestFilterExceptionHandler
 */
class RateLimiterExceptionHandler extends AbstractExceptionHandler
{
    public function handle(Exception $exception, bool $subsequent = false): Exception|callable
    {
        if ($exception instanceof TooManyRequestsException) {
            return static function (HttpResponseInterface $response) use ($exception) {
                $response->setHeader(RequestFilterException::MESSAGE_SEVERITY_HEADER, $exception->severity()->type(), true);
                $response->setBody($exception->getMessage());
                $response->setHttpResponseCode($exception->status());

                return $response;
            };
        }

        return $exception;
    }
}
