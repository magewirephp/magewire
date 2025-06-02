<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magento\Framework\App\Config as SystemConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig as EnvironmentConfig;

class Config
{
    public function __construct(
        private readonly EnvironmentConfig $environmentConfig,
        private readonly SystemConfig $systemConfig,
        private readonly array $paths = []
    ) {
        //
    }

    /**
     * Retrieve configuration settings from either the .env file
     * or config.xml based on the specified path.
     *
     * @return array|mixed|string|null
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function getValue(
        string $path,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        if (array_key_exists($path, $this->paths)) {
            $path = $this->paths[$path];
        }

        return $this->systemConfig->getValue($path, $scope, $scopeCode)
            ?? ($this->environmentConfig->isAvailable() ? $this->environmentConfig->get($path) : null);
    }
}
