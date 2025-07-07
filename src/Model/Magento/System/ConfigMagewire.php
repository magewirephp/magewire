<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Magento\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class ConfigMagewire
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Json $serializer
    ) {
        //
    }

    /**
     * Retrieve grouped config value by path and scope.
     */
    public function getGroupValue(
        string $path,
        ?string $group = null,
        string $scopeType = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): mixed {
        return $this->scopeConfig->getValue($this->createPath($path, $group), $scopeType, $scopeCode);
    }

    /**
     * Retrieve grouped config flag by path and scope.
     */
    public function isGroupFlag(
        string $path,
        ?string $group = null,
        string $scopeType = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag($this->createPath($path, $group), $scopeType, $scopeCode);
    }

    /**
     * Returns a formatted path based on the provided path and optional group.
     */
    private function createPath(string $path, ?string $group = null)
    {
        return sprintf('dev/%s/%s', $group ? 'magewire/' . $group : 'magewire', trim($path));
    }
}
