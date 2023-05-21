<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Magento\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class ConfigMagewire
{
    public const GROUP_LOADER = 'loader';
    public const GROUP_NOTIFICATIONS = 'loader/notifications';

    protected ScopeConfigInterface $scopeConfig;
    protected Json $serializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    public function canShowLoaderOverlay(): bool
    {
        return $this->isGroupFlag('enable', self::GROUP_LOADER) ?? true;
    }

    public function canShowLoaderNotificationMessages(): bool
    {
        return $this->isGroupFlag('enable', self::GROUP_NOTIFICATIONS) ?? true;
    }

    public function getNotificationMessageFadeoutTimeout(): int
    {
        return (int) $this->getGroupValue('message_fadeout_timeout', self::GROUP_NOTIFICATIONS) ?? 2500;
    }

    public function pageRequiresLoaderPluginScript(): bool
    {
        return $this->canShowLoaderOverlay() || $this->canShowLoaderNotificationMessages();
    }

    /**
     * Retrieve grouped config value by path and scope.
     *
     * @return mixed
     */
    public function getGroupValue(
        string $path,
        string $group = null,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ) {
        return $this->scopeConfig->getValue($this->createPath($path, $group), $scopeType, $scopeCode);
    }

    /**
     * Retrieve grouped config flag by path and scope.
     */
    public function isGroupFlag(
        string $path,
        string $group = null,
        string $scopeType = ScopeInterface::SCOPE_STORE,
               $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag($this->createPath($path, $group), $scopeType, $scopeCode);
    }

    protected function createPath(string $path, string $group = null)
    {
        return sprintf('dev/%s/%s', $group ? 'magewire/' . $group : 'magewire', trim($path));
    }
}
