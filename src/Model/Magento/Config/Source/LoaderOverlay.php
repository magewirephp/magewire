<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Magento\Config\Source;

class LoaderOverlay implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @param array<string, string> $options
     */
    public function __construct(
        array $options = []
    ) {
        $this->options = array_merge($options, [
            'overlay' => 'Full screen',
            'component-overlay' => 'Component only'
        ]);
    }

    public function toOptionArray() :array
    {
        return array_map(fn ($label, $value) => ['value' => $value, 'label' => ucfirst($label)], $this->options, array_keys($this->options));
    }
}
