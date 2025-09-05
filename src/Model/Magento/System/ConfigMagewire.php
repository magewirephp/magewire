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
use Magento\Store\Model\ScopeInterface;

class ConfigMagewire
{
    public const GROUP_FEATURES = 'features';
    public const GROUP_DEBUG = 'debug';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
        //
    }

    /**
     * Retrieve grouped config value by path and scope.
     */
    public function getGroupValue(
        string $path,
        string $group = null,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): mixed {
        return $this->scopeConfig->getValue($this->createPath($path, $group), $scopeType, $scopeCode);
    }

    /**
     * Retrieve a grouped config flag by path and scope.
     */
    public function isGroupFlag(
        string $path,
        string $group = null,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag($this->createPath($path, $group), $scopeType, $scopeCode);
    }

    /**
     * Retrieve features group config value by path and scope.
     */
    public function getFeaturesGroupValue(
        string $path,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): mixed {
        return $this->getGroupValue($path, self::GROUP_FEATURES, $scopeType, $scopeCode);
    }

    /**
     * Retrieve a features group config flag by path and scope.
     */
    public function isFeatureGroupFlag(
        string $path,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag($this->createPath($path, self::GROUP_FEATURES), $scopeType, $scopeCode);
    }

    /**
     * Retrieve debug group config value by path and scope.
     */
    public function getDebugGroupValue(
        string $path,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): mixed {
        return $this->getGroupValue($path, self::GROUP_DEBUG, $scopeType, $scopeCode);
    }

    /**
     * Retrieve a debug group config flag by path and scope.
     */
    public function isDebugGroupFlag(
        string $path,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag($this->createPath($path, self::GROUP_DEBUG), $scopeType, $scopeCode);
    }

    /**
     * Returns a formatted path based on the provided path and optional group.
     */
    private function createPath(string $path, string $group = null): string
    {
        return $group
            ? sprintf('magewire/%s/%s', $group, trim($path))
            : sprintf('magewire/%s', trim($path));
    }
}
