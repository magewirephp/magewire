<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleRequests\ComponentRequestContext;

class UpdateRequestRateLimiter extends RateLimiter
{
    public function validateWithComponentRequestContext(ComponentRequestContext $componentRequestContext): bool
    {
        $key = $this->generateKeyByRequestContext($componentRequestContext);
        $result = $this->validate($key, 2);

        if ($result) {
            $this->hit($key);
        }

        return $result;
    }

    public function validateWithComponent(Component $component): bool
    {
        return true;
    }

    private function generateKeyByRequestContext(ComponentRequestContext $componentRequestContext): string
    {
        return 'blaaaaa';
    }
}
