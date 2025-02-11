<?php

namespace Magewirephp\Magewire\Helper;

use Magento\Csp\Helper\CspNonceProvider as MagentoCspNonceProvider;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Magento Csp Nonce Provider doesn't extend the Abstract Helper
 * and there it's impossible to use it directly in existing templates
 *
 * Use this (escaped version which otherwise breaks PHP doc) script initiator to correctly generate a nonce for the script
 * <script nonce="<?= /* @noEscape *\/ $this->helper(\Magewirephp\Magewire\Helper\CspNonceProvider::class)->generateNonce() ?\>">
 */
class CspNonceProvider extends AbstractHelper
{
    private MagentoCspNonceProvider $nonceProvider;

    public function __construct(
        Context                 $context,
        MagentoCspNonceProvider $nonceProvider,
    ) {
        parent::__construct($context);
        $this->nonceProvider = $nonceProvider;
    }

    public function generateNonce(): string
    {
        return $this->nonceProvider->generateNonce();
    }
}