<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Handler;

use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Api\MultipleRootElementDetectionHandlerInterface;
use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\MultipleRootElementsDetectedException;
use Psr\Log\LoggerInterface;

/**
 * Silent-to-the-user behavior: record the violation in the server log and render on.
 */
class ServerLogHandler implements MultipleRootElementDetectionHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(object $component, string $html, int $rootCount): string
    {
        $this->logger->warning(sprintf('Magewire: %s', MultipleRootElementsDetectedException::buildMessage($component)));

        return $html;
    }
}
