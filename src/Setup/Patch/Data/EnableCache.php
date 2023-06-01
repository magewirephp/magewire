<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magewirephp\Magewire\Model\Cache\Type\Magewire as MagewireCacheType;

class EnableCache implements DataPatchInterface
{
    protected CacheManager $cacheManager;

    public function __construct(
        CacheManager $cacheManager
    ) {
        $this->cacheManager = $cacheManager;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        // Make sure the Magewire cache is enabled.
        $enableMagewireCache = $this->cacheManager->setEnabled(
            array_intersect($this->cacheManager->getAvailableTypes(), [MagewireCacheType::TYPE_IDENTIFIER]),
            true
        );

        $this->cacheManager->clean($enableMagewireCache);
        return $this;
    }
}
