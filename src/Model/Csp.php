<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model;

use Magento\Framework\App\ObjectManager;

class Csp
{
    const HASH_ALGORITHM = 'sha256';

    private null|false|object $nonceProvider = null;

    public function getMagentoCspNonceProvider(): object|bool
    {
        /*
         * By default, we check whether the $nonceProvider has not yet been set. If it hasn't, we verify if the
         * CSP requirements are met. If they are, we bind the CspNonceProvider via the Object Manager to prevent
         * compilation exceptions that could arise from using dependency injection.
         *
         * This approach also ensures backward compatibility for Magento installations that do not yet include
         * the CspNonceProvider class, allowing projects to upgrade Magewire without issues.
         */
        if ($this->nonceProvider === null) {
            $this->nonceProvider = $this->isCspAvailable()
                ? ObjectManager::getInstance()->get('Magento\Csp\Helper\CspNonceProvider')
                : false;
        }

        return $this->nonceProvider;
    }

    public function isCspAvailable(): bool
    {
        return ($this->nonceProvider !== false && class_exists('Magento\Csp\Helper\CspNonceProvider'))
            || (is_object($this->nonceProvider) && method_exists($this->nonceProvider, 'generateNonce'));
    }

    public function generateHash(string $content): string
    {
        return base64_encode(hash(self::HASH_ALGORITHM, $content, true));
    }

    public function getHashAlgorithm(): string
    {
        return self::HASH_ALGORITHM;
    }
}
