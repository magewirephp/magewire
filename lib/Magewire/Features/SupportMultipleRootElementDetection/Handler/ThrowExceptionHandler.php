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

/**
 * Default behavior: throw, aborting the render. Preserves the original detection contract.
 */
class ThrowExceptionHandler implements MultipleRootElementDetectionHandlerInterface
{
    public function handle(object $component, string $html, int $rootCount): string
    {
        throw new MultipleRootElementsDetectedException($component);
    }
}
