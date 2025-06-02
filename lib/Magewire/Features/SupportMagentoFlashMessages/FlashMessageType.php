<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoFlashMessages;

enum FlashMessageType: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Notice = 'notice';
    case Success = 'success';
}
