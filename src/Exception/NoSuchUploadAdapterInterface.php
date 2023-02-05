<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Exception;

use Magento\Framework\Exception\LocalizedException;

class NoSuchUploadAdapterInterface extends MagewireException
{
    public function __construct(string $name, Exception $cause = null, $code = 0)
    {
        parent::__construct(
            __('No such upload adapter.'),
            $cause,
            $code
        );
    }
}
