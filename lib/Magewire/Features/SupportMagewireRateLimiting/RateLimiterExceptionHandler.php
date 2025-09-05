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
use Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Exceptions\TooManyRequestsException;
use Magewirephp\Magewire\Model\App\AbstractExceptionHandler;

class RateLimiterExceptionHandler extends AbstractExceptionHandler
{
    public function handle(Exception $exception, bool $subsequent = false): Exception|callable
    {
        if ($exception instanceof TooManyRequestsException) {
            return static function (HttpResponseInterface $response) use ($exception) {
                $response->setBody('Too Many Requests.');
                $response->setHttpResponseCode(429);

                return $response;
            };
        }

        return $exception;
    }
}
