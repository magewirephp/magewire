<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Behavior options for multiple-root-element detection. The string values double as the
 * keys of the handler pool (see MultipleRootElementDetectionHandlerManager), so a third
 * party extends by adding both a pool item and an option here under the same key.
 */
class DetectionBehavior implements OptionSourceInterface
{
    public const EXCEPTION = 'exception';
    public const CONSOLE = 'console';
    public const LOG = 'log';
    public const OFF = 'off';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::EXCEPTION, 'label' => 'Throw exception (default)'],
            ['value' => self::CONSOLE, 'label' => 'Browser console error'],
            ['value' => self::LOG, 'label' => 'Server log'],
            ['value' => self::OFF, 'label' => 'Off']
        ];
    }
}
