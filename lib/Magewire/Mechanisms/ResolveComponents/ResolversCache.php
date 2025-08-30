<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\ResolveComponents;

use Magewirephp\Magento\App\Cache\MagewireCacheSection;

class ResolversCache extends MagewireCacheSection
{
    protected string $identifier = 'resolvers';
}
