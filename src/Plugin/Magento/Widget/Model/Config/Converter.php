<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Plugin\Magento\Widget\Model\Config;

use Magento\Widget\Model\Config\Converter as Subject;
use Magewirephp\Magewire\Model\Component\WidgetCollection;

class Converter
{
    protected WidgetCollection $widgetCollection;

    public function __construct(
        WidgetCollection $widgetCollection
    ) {
        $this->widgetCollection = $widgetCollection;
    }

    public function afterConvert(Subject $subject, array $widgets): array
    {
        return array_map(function ($widget, $key) {
            if ($this->widgetCollection->has($key)) {
                $widget['parameters']['magewire'] = [
                    'type' => 'text',
                    'value' => $key,
                    'visible' => '0',
                    'required' => '1',
                ];
            }

            return $widget;
        }, $widgets, array_keys($widgets));
    }
}
