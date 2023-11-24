<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class NoSuchUploadAdapterInterface extends MagewireException
{
    public function __construct(Phrase $phrase, \Exception $cause = null, $code = 0)
    {
        parent::__construct(
            __('No such upload adapter.'),
            $cause,
            $code
        );
    }
}
