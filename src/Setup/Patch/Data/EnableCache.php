This overrides the previous default page layout checkout, which is set in checkout_index_index.xml and hyva_checkout_index_index.xml.
This means, this is a backwards incompatible change that will likely affect everybody.
For this reason, I suggest not to make this change, but keep the previous page layout checkout.
To fix the positioning of the logo in the header, best add the blocks to the checkout page layout.
We have to take care this doesn't affect luma checkouts.

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
            array_intersect($this->cacheManager->getAvailableTypes(), [MagewireCacheType::TYPE_IDENTIFIER]), true
        );

        $this->cacheManager->clean($enableMagewireCache);
        return $this;
    }
}
