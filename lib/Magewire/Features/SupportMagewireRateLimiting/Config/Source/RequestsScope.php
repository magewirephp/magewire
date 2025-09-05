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

class RequestsScope implements OptionSourceInterface
{
    public const ISOLATED = '1';
    public const SHARED = '0';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::ISOLATED, 'label' => 'Isolated'],
            ['value' => self::SHARED, 'label' => 'Shared']
        ];
    }
}
