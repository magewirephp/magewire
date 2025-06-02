<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magewirephp\Magento\App\Cache\Type\Magewire as MagewireCacheType;

class EnableCache implements DataPatchInterface
{
    function __construct(
        private readonly CacheManager $cacheManager
    ) {
        //
    }

    static function getDependencies(): array
    {
        return [];
    }

    function getAliases(): array
    {
        return [];
    }

    /**
     * Ensure that the Magewire cache is enabled upon installation.
     */
    function apply(): self
    {
        $enableMagewireCache = $this->cacheManager->setEnabled(
            array_intersect($this->cacheManager->getAvailableTypes(), [MagewireCacheType::TYPE_IDENTIFIER]),
            true
        );

        $this->cacheManager->clean($enableMagewireCache);
        return $this;
    }
}
