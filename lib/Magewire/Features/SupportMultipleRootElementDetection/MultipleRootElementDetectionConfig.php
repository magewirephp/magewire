<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMultipleRootElementDetection;

use Magewirephp\Magewire\Features\SupportMultipleRootElementDetection\Config\Source\DetectionBehavior;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewireGroup;

class MultipleRootElementDetectionConfig extends ConfigMagewireGroup
{
    public function getBehavior(): string
    {
        return $this->config()->getFeaturesGroupValue('multiple_root_element_detection/behavior')
            ?? DetectionBehavior::EXCEPTION;
    }
}
