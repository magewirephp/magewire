<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireRateLimiting\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Enabled implements OptionSourceInterface
{
    public const COMPONENTS_ONLY = '2';
    public const REQUESTS_ONLY = '1';
    public const NONE = '0';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::COMPONENTS_ONLY, 'label' => 'Components only'],
            ['value' => self::REQUESTS_ONLY, 'label' => 'Requests only'],
            ['value' => self::NONE, 'label' => 'None']
        ];
    }
}
