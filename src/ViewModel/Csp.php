<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\ViewModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * @internal For future compatibility, we recommend using a custom implementation or a third-party solution
 *           until Magewire V3 is released. Magewire V3 will introduce a different file structure, which means
 *           this view model may no longer exist in this namespace.
 *
 *           Use this at your own risk. If you choose to proceed, be prepared to adapt your implementation
 *           when migrating to Magewire V3.
 */
class Csp implements ArgumentInterface
{
    private Escaper $escaper;

    /** @var null|false|object $nonceProvider */
    private $nonceProvider = null;

    public function __construct(
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
    }

    public function generateNonce(): string
    {
        return $this->isCspAvailable()
            ? $this->getMagentoCspNonceProvider()->generateNonce()
            : '';
    }

    public function generateNonceAttribute(string $format = ' %s'): string
    {
        return $this->isCspAvailable()
            ? sprintf(sprintf($format, 'nonce="%s"'), $this->escaper->escapeHtmlAttr($this->generateNonce()))
            : '';
    }

    /**
     * @return null|false|object
     */
    private function getMagentoCspNonceProvider()
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
            // phpcs:disable
            $this->nonceProvider = $this->isCspAvailable()
                ? ObjectManager::getInstance()->get('Magento\Csp\Helper\CspNonceProvider')
                : false;
            // phpcs:enable
        }

        return $this->nonceProvider;
    }

    private function isCspAvailable(): bool
    {
        // phpcs:disable
        return ($this->nonceProvider !== false && class_exists('Magento\Csp\Helper\CspNonceProvider'))
            || (is_object($this->nonceProvider) && method_exists($this->nonceProvider, 'generateNonce'));
        // phpcs:enable
    }
}
