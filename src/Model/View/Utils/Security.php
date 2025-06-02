<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magewirephp\Magewire\Model\View\UtilsInterface;
use Psr\Log\LoggerInterface;

class Security implements UtilsInterface
{
    public function __construct(
        private readonly FormKey $formKey,
        private readonly LoggerInterface $logger
    ) {
        //
    }

    function getCsrfToken(): string
    {
        try {
            return $this->formKey->getFormKey();
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        return 'unknown-csrf-token';
    }
}
