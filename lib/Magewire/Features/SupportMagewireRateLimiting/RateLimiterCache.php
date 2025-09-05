<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting;

use Magewirephp\Magento\App\Cache\MagewireCacheSection;

class RateLimiterCache extends MagewireCacheSection
{
    protected string $identifier = 'rate-limiter';
}
