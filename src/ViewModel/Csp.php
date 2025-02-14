<?php

namespace Magewirephp\Magewire\ViewModel;

use Magento\Csp\Helper\CspNonceProvider as MagentoCspNonceProvider;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Csp implements ArgumentInterface
{
    private MagentoCspNonceProvider $nonceProvider;
    private Escaper $escaper;

    public function __construct(
        MagentoCspNonceProvider $nonceProvider,
        Escaper $escaper
    ) {
        $this->nonceProvider = $nonceProvider;
        $this->escaper = $escaper;
    }

    public function generateNonce($includeAttribute = false): string
    {
        $nonce = $this->nonceProvider->generateNonce();

        if (! $includeAttribute) {
            return $nonce;
        }

        return sprintf(
            ' nonce="%s"',
            $this->escaper->escapeHtmlAttr($nonce)
        );
    }
}