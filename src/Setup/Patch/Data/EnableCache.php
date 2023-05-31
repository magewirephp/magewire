<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magewirephp\Magewire\Model\Cache\Type\Magewire as CacheType;

class EnableCache implements DataPatchInterface
{
    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct(
        WriterInterface $writer,
        CacheManager $CacheManager
    ) {
        $this->writer = $writer;
        $this->cacheManager = $CacheManager;
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
        $types = [CacheType::TYPE_IDENTIFIER];
        $availableTypes = $this->cacheManager->getAvailableTypes();
        $types = array_intersect($availableTypes, $types);
        $enabledTypes = $this->cacheManager->setEnabled($types, true);
        $this->cacheManager->clean($enabledTypes);
        return $this;
    }
}
