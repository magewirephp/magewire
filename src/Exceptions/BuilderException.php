<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Exceptions;

use Magento\Framework\Exception\LocalizedException;

class BuilderException extends LocalizedException
{
    public static function couldNotCreateComponent(): self
    {
        return new self(__('test'));
    }
}
