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

/**
 * Disabled behavior: do nothing. Normally the manager short-circuits before the HTML is
 * even parsed when "off" is selected; this remains as a defensive no-op for the pool.
 */
class NoopHandler implements MultipleRootElementDetectionHandlerInterface
{
    public function handle(object $component, string $html, int $rootCount): string
    {
        return $html;
    }
}
