<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magento\Framework\Escaper;
use Magewirephp\Magewire\Model\View\UtilsInterface;

class Csp implements UtilsInterface
{
    public function __construct(
        private readonly Escaper $escaper,
        private readonly \Magewirephp\Magewire\Model\Csp $csp
    ) {
        //
    }

    public function generateNonce(): string
    {
        return $this->csp->isCspAvailable()
            ? $this->csp->getMagentoCspNonceProvider()->generateNonce()
            : '';
    }

    public function generateNonceAttribute(string $format = ' %s'): string
    {
        return $this->csp->isCspAvailable()
            ? sprintf(sprintf($format, 'nonce="%s"'), $this->escaper->escapeHtmlAttr($this->generateNonce()))
            : '';
    }
}
